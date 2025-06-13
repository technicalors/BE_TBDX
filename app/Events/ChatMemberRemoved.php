<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMemberRemoved implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $chatId;
    public $userId;

    public function __construct($chatId, $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
        ];
    }
}