<?php

namespace App\Notifications;

use App\Models\Memo;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemoNotification extends Notification
{
    public function __construct(
        public Memo $memo,
        public string $title,
        public string $message,
        public ?string $mailMessage = null,
        private bool $databaseOnly = false,
        private bool $emailAdministrator = false,
    ) {}

    public function via(object $notifiable): array
    {
        if (
            $this->databaseOnly
            || ($notifiable instanceof User && $notifiable->hasRole('administrator') && ! $this->emailAdministrator)
        ) {
            return ['database'];
        }

        $channels = ['database'];

        if (
            $notifiable instanceof User
            && $notifiable->is_active
            && is_string($notifiable->email)
            && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)
            && ! str_ends_with(strtolower($notifiable->email), '.invalid')
        ) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->memo->loadMissing([
            'items',
            'branch',
            'division',
            'submitter.division',
            'onBehalfOf',
            'currentApprovals.approverRole',
            'currentApprovals.actor',
        ]);
        $direksi = User::whereHas('role', fn ($query) => $query->where('slug', 'direksi'))
            ->where('is_active', true)
            ->first();

        return (new MailMessage)
            ->subject("[Internal Memo] {$this->title} — {$this->memo->memo_number}")
            ->view(['html' => 'emails.memo', 'text' => 'emails.memo_text'], [
                'title' => $this->title,
                'body' => $this->mailMessage ?? $this->message,
                'memo' => $this->memo,
                'direksi' => $direksi,
                'memoUrl' => route('memos.show', $this->memo),
                'buttonLabel' => 'Buka Memo',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'memo_id' => $this->memo->id,
            'memo_number' => $this->memo->memo_number,
            'title' => $this->title,
            'message' => $this->message,
            'url' => route('memos.show', $this->memo),
        ];
    }
}
