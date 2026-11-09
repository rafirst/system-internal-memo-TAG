<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\MemoAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Memo $memo, MemoAttachment $attachment)
    {
        Gate::authorize('view', $memo);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
