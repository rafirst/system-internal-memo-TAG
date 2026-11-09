<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestEmailNotification extends Notification
{
    public function via(object $notifiable): array
    {
        if (
            ! ($notifiable instanceof User)
            || ! $notifiable->is_active
            || ! is_string($notifiable->email)
            || ! filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)
            || str_ends_with(strtolower($notifiable->email), '.invalid')
        ) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Internal Memo] Email Tes')
            ->view(['html' => 'emails.memo', 'text' => 'emails.memo_text'], [
                'recipientName' => $notifiable->name,
                'title' => 'Email tes sistem Internal Memo',
                'body' => 'Ini adalah email tes untuk memastikan alamat email Anda dapat menerima notifikasi sistem.',
                'memo' => null,
                'submitter' => null,
                'statusLabel' => null,
                'memoUrl' => route('dashboard'),
                'buttonLabel' => 'Buka Sistem',
            ]);
    }
}
