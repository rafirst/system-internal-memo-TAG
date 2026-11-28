<?php

namespace App\Http\Controllers;

use App\Enums\MemoStatus;
use App\Http\Requests\MemoRequest;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Memo;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\MemoNumberGenerator;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MemoController extends Controller
{
    public function __construct(
        private ApprovalService $service,
        private MemoNumberGenerator $numbers,
    ) {}

    public function index(Request $request)
    {
        $dateToRules = ['nullable', 'date'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_map(
                fn (MemoStatus $status) => $status->value,
                MemoStatus::cases(),
            ))],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => $dateToRules,
        ]);

        $memos = Memo::visibleTo($request->user())
            ->with(['submitter', 'onBehalfOf', 'division'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['division_id'] ?? null, fn ($query, $divisionId) => $query->where('division_id', $divisionId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('memo_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('memo_date', '<=', $date))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(
                fn ($searchQuery) => $searchQuery
                    ->where('memo_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('submitter', fn ($submitterQuery) => $submitterQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('onBehalfOf', fn ($onBehalfOfQuery) => $onBehalfOfQuery->where('name', 'like', "%{$search}%"))
            ))
            ->latest()->paginate(15)->withQueryString();

        return view('memos.index', [
            'memos' => $memos,
            'statuses' => MemoStatus::cases(),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('memos.form', [
            'memo' => null,
            'divisions' => $user->hasRole('administrator') ? Division::orderBy('name')->get() : collect(),
            'branches' => $user->hasRole('administrator') ? Branch::orderBy('name')->get() : collect(),
            'approvers' => $this->eligibleApprovers($user->id),
        ]);
    }

    public function store(MemoRequest $request)
    {
        Gate::authorize('create', Memo::class);
        $user = $request->user();

        $memo = DB::transaction(function () use ($request, $user) {
            $divisionId = $user->hasRole('administrator')
                ? (int) $request->validated('division_id')
                : $user->division_id;
            $branchId = $user->hasRole('administrator')
                ? (int) $request->validated('branch_id')
                : $user->branch_id;

            $memo = Memo::create([
                ...$this->memoAttributes($request),
                'approver_ids' => $request->validated('approver_ids', []),
                'submitter_id' => $user->id,
                'on_behalf_of_id' => $user->hasRole('spv') ? null : $this->supervisorOf($divisionId)?->id,
                'division_id' => $divisionId,
                'branch_id' => $branchId,
                'status' => MemoStatus::Draft,
                'total_amount' => 0,
            ]);

            $this->syncItems($memo, $this->itemsFor($request));
            $this->service->attach($memo, $request->file('attachments', []), 'supporting', $user);

            return $memo;
        });

        return $this->afterSave($request, $memo, 'Draft memo tersimpan.');
    }

    public function show(Memo $memo)
    {
        Gate::authorize('view', $memo);
        $memo->load(['items', 'attachments.uploader', 'division', 'branch', 'submitter', 'onBehalfOf',
            'approvals.approverRole', 'approvals.assignedApprover', 'approvals.actor']);

        return view('memos.show', compact('memo'));
    }

    public function edit(Request $request, Memo $memo)
    {
        Gate::authorize('update', $memo);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $memo->load(['items', 'attachments', 'approvals.approverRole', 'approvals.assignedApprover', 'approvals.actor']);

        return view('memos.form', [
            'memo' => $memo,
            'approvers' => $this->eligibleApprovers($user->id),
        ]);
    }

    public function update(MemoRequest $request, Memo $memo)
    {
        $pendingApproversChanged = false;

        DB::transaction(function () use ($request, $memo, &$pendingApproversChanged) {
            $memo = Memo::lockForUpdate()->findOrFail($memo->id);
            Gate::authorize('update', $memo);

            $memo->update($this->memoAttributes($request));
            if ($memo->status === MemoStatus::Pending && $request->exists('approver_ids_present')) {
                $pendingApproversChanged = $this->service->syncPendingApprovers($memo);
            }
            $this->syncItems($memo, $this->itemsFor($request));

            foreach ($memo->attachments()->where('type', 'supporting')->whereIn('id', $request->input('remove_attachments', []))->get() as $att) {
                $this->service->deleteAttachment($att);
            }
            $this->service->attach($memo, $request->file('attachments', []), 'supporting', $request->user());
        });

        if ($pendingApproversChanged) {
            $this->service->notifyCurrentApprovers($memo->fresh());
        }

        return $this->afterSave($request, $memo, 'Memo diperbarui.');
    }

    public function destroy(Memo $memo)
    {
        DB::transaction(function () use ($memo): void {
            $memo = Memo::query()->lockForUpdate()->findOrFail($memo->id);
            Gate::authorize('delete', $memo);

            DB::table('notifications')
                ->where('data->memo_id', $memo->id)
                ->delete();

            $memo->delete();
            $this->numbers->release($memo);
        });

        Storage::disk('local')->deleteDirectory("memos/{$memo->id}");

        return redirect()->route('memos.index')->with('success', 'Memo berhasil dihapus.');
    }

    // ----------------------------------------------------------------------

    private function afterSave(Request $request, Memo $memo, string $savedMessage)
    {
        if ($request->input('action') === 'submit') {
            try {
                $memo = $this->service->submit($memo, $request->user());

                return redirect()->route('memos.show', $memo)
                    ->with('success', "Memo diajukan dengan nomor {$memo->memo_number}.");
            } catch (DomainException $e) {
                return redirect()->route('memos.show', $memo)
                    ->with('error', 'Perubahan tersimpan, tetapi memo belum diajukan: '.$e->getMessage());
            }
        }

        return redirect()->route('memos.show', $memo)->with('success', $savedMessage);
    }

    private function syncItems(Memo $memo, array $items): void
    {
        $memo->items()->delete();
        $total = 0;

        foreach ($items as $item) {
            $qty = isset($item['qty']) && $item['qty'] !== '' ? (int) $item['qty'] : null;
            $price = (float) $item['unit_price'];
            $subtotal = ($qty ?? 1) * $price;
            $total += $subtotal;

            $memo->items()->create([
                'name' => $item['name'],
                'qty' => $qty,
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ]);
        }

        $memo->update(['total_amount' => $total]);
    }

    /**
     * @return array<string, mixed>
     */
    private function memoAttributes(MemoRequest $request): array
    {
        $memoKind = $request->validated('memo_kind');
        $isPameran = $memoKind === 'pameran';

        $attributes = [
            ...$request->safe()->only(['subject', 'opening_paragraph', 'memo_date']),
            'memo_kind' => $memoKind,
            'pameran_details' => $isPameran ? $request->validated('pameran') : null,
            'bank_name' => $isPameran ? null : $request->validated('bank_name'),
            'account_number' => $isPameran ? null : $request->validated('account_number'),
            'account_holder' => $isPameran ? null : $request->validated('account_holder'),
        ];

        if ($request->exists('approver_ids_present') || $request->exists('approver_ids')) {
            $attributes['approver_ids'] = $request->validated('approver_ids', []);
        }

        return $attributes;
    }

    /**
     * @return array<int, array{name: string, qty: int, unit_price: float|int|string}>
     */
    private function itemsFor(MemoRequest $request): array
    {
        if ($request->validated('memo_kind') === 'pengajuan') {
            return $request->validated('items');
        }

        return [[
            'name' => 'Estimasi biaya Pameran (maksimum)',
            'qty' => 1,
            'unit_price' => $request->validated('pameran.budget_max'),
        ]];
    }

    /** Spv aktif di divisi yang sama (satu per divisi). */
    private function supervisorOf(int $divisionId): ?User
    {
        return User::whereHas('role', fn ($q) => $q->where('slug', 'spv'))
            ->where('division_id', $divisionId)
            ->where('is_active', true)
            ->first();
    }

    private function eligibleApprovers(int $submitterId): Collection
    {
        return User::with(['role', 'branch'])
            ->where('is_active', true)
            ->where('id', '!=', $submitterId)
            ->whereHas('role', fn ($query) => $query->where('slug', '!=', 'staff'))
            ->orderBy('role_id')
            ->orderBy('name')
            ->get();
    }
}
