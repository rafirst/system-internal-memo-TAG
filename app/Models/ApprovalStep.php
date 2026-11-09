<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = ['approval_flow_id', 'step_order', 'role_id', 'label'];

    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
