<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoSequence extends Model
{
    protected $fillable = ['division_id', 'year', 'last_number'];
}
