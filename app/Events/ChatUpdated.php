<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        // eager-load participants
        $this->chat = $chat;
    }

    public function broadcastOn()
    {
        return collect($this->chat->participants)
        ->map(fn ($user) => new PrivateChannel('user.' . $user->id))
        ->all();
    }

    public function broadcastWith(): array
    {
        return $this->chat->toArray();
    }
}