<?php

namespace App\Http\Controllers;

use App\Services\MemoActivityFeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    public function index(Request $request, MemoActivityFeed $activityFeed)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'in:all,updated,approval,denied'],
        ]);
        $category = $filters['category'] ?? 'all';
        $user = $request->user();
        $isActivityFeed = $user->hasRole('administrator');

        if ($isActivityFeed) {
            $activities = $activityFeed->allForUser($user);
            $categories = $this->activityCategories($activities);
            $visibleActivities = $category === 'all'
                ? $activities
                : $activities->filter(fn (array $activity): bool => $this->activityCategory($activity['type']) === $category)->values();
            $notifications = $this->paginateActivities($visibleActivities, $request);
            $unreadNotifications = 0;
        } else {
            $notificationQuery = $user->notifications()->getQuery();
            $categories = [
                'all' => ['label' => 'Semua', 'count' => (clone $notificationQuery)->count()],
                'updated' => [
                    'label' => 'Diperbarui',
                    'count' => $this->categoryQuery(clone $notificationQuery, 'updated')->count(),
                ],
                'approval' => [
                    'label' => 'Persetujuan',
                    'count' => $this->categoryQuery(clone $notificationQuery, 'approval')->count(),
                ],
                'denied' => [
                    'label' => 'Ditolak',
                    'count' => $this->categoryQuery(clone $notificationQuery, 'denied')->count(),
                ],
            ];
            $notifications = $this->categoryQuery(clone $notificationQuery, $category)
                ->latest()
                ->paginate(8)
                ->withQueryString();
            $unreadNotifications = $user->unreadNotifications()->count();
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'categories' => $categories,
            'activeCategory' => $category,
            'totalNotifications' => $categories['all']['count'],
            'approvalNotifications' => $categories['approval']['count'],
            'deniedNotifications' => $categories['denied']['count'],
            'updatedNotifications' => $categories['updated']['count'],
            'unreadNotifications' => $unreadNotifications,
            'isActivityFeed' => $isActivityFeed,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return array<string, array{label: string, count: int}>
     */
    private function activityCategories(Collection $activities): array
    {
        return [
            'all' => ['label' => 'Semua', 'count' => $activities->count()],
            'updated' => [
                'label' => 'Diperbarui',
                'count' => $activities->filter(fn (array $activity): bool => $this->activityCategory($activity['type']) === 'updated')->count(),
            ],
            'approval' => [
                'label' => 'Persetujuan',
                'count' => $activities->filter(fn (array $activity): bool => $this->activityCategory($activity['type']) === 'approval')->count(),
            ],
            'denied' => [
                'label' => 'Ditolak',
                'count' => $activities->filter(fn (array $activity): bool => $this->activityCategory($activity['type']) === 'denied')->count(),
            ],
        ];
    }

    private function activityCategory(string $type): string
    {
        return match ($type) {
            MemoActivityFeed::APPROVED => 'approval',
            MemoActivityFeed::DENIED => 'denied',
            MemoActivityFeed::DISBURSED, MemoActivityFeed::COMPLETED => 'updated',
            default => 'all',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     */
    private function paginateActivities(Collection $activities, Request $request): LengthAwarePaginator
    {
        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $activities->forPage($page, $perPage)
            ->values()
            ->map(fn (array $activity): object => (object) [
                'data' => [
                    'title' => $activity['title'],
                    'message' => $activity['message'],
                    'memo_number' => $activity['memo_number'],
                    'url' => $activity['url'],
                ],
                'read_at' => null,
                'created_at' => $activity['at'],
            ]);

        return new LengthAwarePaginator(
            $items,
            $activities->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    private function categoryQuery(Builder $query, string $category): Builder
    {
        if ($category === 'denied') {
            return $query->where('data->title', 'like', '%ditolak%');
        }

        if ($category === 'approval') {
            return $query->where(function (Builder $query): void {
                $query->where('data->title', 'like', '%persetujuan%')
                    ->orWhere('data->title', 'like', '%disetujui%');
            });
        }

        if ($category === 'updated') {
            return $query->where(function (Builder $query): void {
                $query->where('data->title', 'like', '%diperbarui%')
                    ->orWhere('data->title', 'like', '%dicairkan%')
                    ->orWhere('data->title', 'like', '%diunggah%');
            })->where('data->title', 'not like', '%persetujuan%')
                ->where('data->title', 'not like', '%disetujui%');
        }

        return $query;
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
