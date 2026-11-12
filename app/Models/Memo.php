<?php

namespace App\Models;

use App\Enums\MemoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Memo extends Model
{
    protected $fillable = [
        'memo_number', 'submitter_id', 'on_behalf_of_id', 'division_id', 'branch_id',
        'subject', 'opening_paragraph', 'total_amount',
        'memo_kind', 'pameran_details',
        'bank_name', 'account_number', 'account_holder',
        'memo_date', 'approver_ids', 'status', 'current_step', 'round',
        'submitted_at', 'approved_at', 'disbursed_at', 'disbursed_by', 'completed_at',
    ];

    protected $casts = [
        'status' => MemoStatus::class,
        'pameran_details' => 'array',
        'approver_ids' => 'array',
        'memo_date' => 'date',
        'total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function onBehalfOf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'on_behalf_of_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /** Admin Keuangan yang mencairkan dana. */
    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MemoItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MemoAttachment::class);
    }

    /** Semua riwayat approval (semua ronde). */
    public function approvals(): HasMany
    {
        return $this->hasMany(MemoApproval::class)->orderBy('round')->orderBy('step_order');
    }

    /** Approval ronde yang sedang berjalan. */
    public function currentApprovals(): HasMany
    {
        return $this->approvals()->where('round', $this->round);
    }

    /**
     * Memo yang boleh dilihat user:
     * Administrator sees all memos, supervisors see their division, and other
     * users see their own memos plus memos assigned to them for approval.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role?->slug) {
            'administrator' => $query,
            'spv' => $query->where('division_id', $user->division_id)->where('status', '!=', 'draft'),
            default => $query->where(fn ($visibleMemos) => $visibleMemos
                ->where('submitter_id', $user->id)
                ->orWhere(fn ($approverMemos) => $approverMemos
                    ->where('status', '!=', 'draft')
                    ->whereHas('approvals', fn ($a) => $a->where(function ($approvals) use ($user) {
                        $approvals->where('assigned_approver_id', $user->id)
                            ->orWhere(fn ($legacyApprovals) => $legacyApprovals
                                ->whereNull('assigned_approver_id')
                                ->where('approver_role_id', $user->role_id));
                    })))),
        };
    }
}
