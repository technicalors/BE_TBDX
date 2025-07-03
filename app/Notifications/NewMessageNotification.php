<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected Message $message;

    public function __construct(Message $message)
    {
        // Load relations if cần: sender, chat
        $this->message = $message->load(['chat']);
    }

    // Kênh notification: database + broadcast
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    // Định nghĩa payload lưu vào DB
    public function toDatabase($notifiable): array
    {
        return $this->message->toArray();
    }

    // Định nghĩa payload broadcast qua WebSocket
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_id' => $this->id,
            'data'            => $this->toDatabase($notifiable),
        ]);
    }
}
