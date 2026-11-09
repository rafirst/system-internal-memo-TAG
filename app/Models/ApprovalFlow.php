<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalFlow extends Model
{
    protected $fillable = ['name', 'submitter_role_id', 'division_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class)->orderBy('step_order');
    }

    public function submitterRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'submitter_role_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
