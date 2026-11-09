<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'memo_number',
        'user_id',
        'memo_type_id',
        'approval_flow_id',
        'title',
        'description',
        'estimated_amount',
        'current_step',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function memoType()
    {
        return $this->belongsTo(MemoType::class);
    }

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    // urut sesuai step_order, ini "riwayat" approval-nya
    public function approvals()
    {
        return $this->hasMany(MemoApproval::class)->orderBy('step_order');
    }

    public function attachments()
    {
        return $this->hasMany(MemoAttachment::class);
    }

    // step yang sedang aktif menunggu approval
    public function currentApproval()
    {
        return $this->hasOne(MemoApproval::class)->where('step_order', $this->current_step);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }
}
