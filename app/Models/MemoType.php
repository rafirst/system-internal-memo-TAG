<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function approvalFlows()
    {
        return $this->hasMany(ApprovalFlow::class);
    }

    public function memoRequests()
    {
        return $this->hasMany(MemoRequest::class);
    }

    // flow aktif yang dipakai saat ini untuk jenis memo ini
    public function activeFlow()
    {
        return $this->hasOne(ApprovalFlow::class)->where('is_active', true)->latestOfMany();
    }
}
