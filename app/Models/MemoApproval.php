<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoApproval extends Model
{
    protected $fillable = [
        'memo_id', 'round', 'step_order', 'approver_role_id', 'assigned_approver_id',
        'status', 'acted_by', 'comment', 'acted_at',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'acted_at' => 'datetime',
    ];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function assignedApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_approver_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
