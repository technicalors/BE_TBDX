<?php

// app/Events/MessageSent.php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MessageRecall implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // eager-load sender and replyTo sender
        $message->loadMissing('chat.participants');
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return collect($this->message->chat->participants)
        ->filter(fn($user) => $user->id !== $this->message->sender_id) // Loại trừ người gửi nếu muốn
        ->map(fn ($user) => new PrivateChannel('user.' . $user->id))
        ->all();
    }

    public function broadcastWith(): array
    {
        return $this->message->toArray();
    }
}
