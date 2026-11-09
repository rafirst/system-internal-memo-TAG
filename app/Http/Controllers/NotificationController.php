<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'in:all,updated,approval,denied'],
        ]);
        $category = $filters['category'] ?? 'all';
        $notificationQuery = $request->user()->notifications()->getQuery();
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

        return view('notifications.index', [
            'notifications' => $notifications,
            'categories' => $categories,
            'activeCategory' => $category,
            'totalNotifications' => $categories['all']['count'],
            'approvalNotifications' => $categories['approval']['count'],
            'deniedNotifications' => $categories['denied']['count'],
            'updatedNotifications' => $categories['updated']['count'],
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
        ]);
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
