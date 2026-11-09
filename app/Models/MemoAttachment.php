<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoAttachment extends Model
{
    protected $fillable = ['memo_id', 'type', 'original_name', 'path', 'uploaded_by'];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
