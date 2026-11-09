<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoItem extends Model
{
    protected $fillable = ['memo_id', 'name', 'qty', 'unit_price', 'subtotal'];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }
}
