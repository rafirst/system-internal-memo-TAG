<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\MemoStatus;
use App\Models\Memo;
use App\Models\User;

class MemoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Memo $memo): bool
    {
        return Memo::visibleTo($user)->whereKey($memo->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator')
            || (($user->hasRole('staff') || $user->hasRole('spv')) && $user->division_id && $user->branch_id);
    }

    public function update(User $user, Memo $memo): bool
    {
        if ($memo->status === MemoStatus::Pending) {
            return ($memo->submitter_id === $user->id || $user->hasRole('administrator'))
                && ! $memo->currentApprovals()
                    ->where('status', ApprovalStatus::Approved->value)
                    ->exists();
        }

        return $memo->submitter_id === $user->id
            && in_array($memo->status, [MemoStatus::Draft, MemoStatus::Denied], true);
    }

    public function delete(User $user, Memo $memo): bool
    {
        if ($memo->submitter_id !== $user->id) {
            return false;
        }

        if ($memo->status === MemoStatus::Draft) {
            return true;
        }

        return $memo->status === MemoStatus::Pending
            && ! $memo->approvals()
                ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Denied->value])
                ->exists();
    }

    public function submit(User $user, Memo $memo): bool
    {
        return $memo->submitter_id === $user->id
            && in_array($memo->status, [MemoStatus::Draft, MemoStatus::Denied], true);
    }

    /** Approve / Deny: hanya role yang sedang mendapat giliran. */
    public function act(User $user, Memo $memo): bool
    {
        return $memo->status === MemoStatus::Pending
            && $memo->approvals()
                ->where('round', $memo->round)
                ->where('step_order', $memo->current_step)
                ->where('status', ApprovalStatus::Pending->value)
                ->where(fn ($query) => $query
                    ->where('assigned_approver_id', $user->id)
                    ->orWhere(fn ($legacyQuery) => $legacyQuery
                        ->whereNull('assigned_approver_id')
                        ->where('approver_role_id', $user->role_id)
                        ->when($user->hasRole('spv'), fn ($supervisorQuery) => $supervisorQuery
                            ->whereHas('memo', fn ($memoQuery) => $memoQuery->where('on_behalf_of_id', $user->id)))))
                ->exists();
    }

    public function disburse(User $user, Memo $memo): bool
    {
        return $user->hasRole('admin_keuangan') && $memo->status === MemoStatus::Approved;
    }

    public function uploadRealization(User $user, Memo $memo): bool
    {
        return $memo->submitter_id === $user->id && $memo->status === MemoStatus::Disbursed;
    }
}
