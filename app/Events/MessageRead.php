<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $chatId;
    public $userId;
    public $messageId;

    public function __construct($chatId, $userId, $messageId)
    {
        $this->chatId    = $chatId;
        $this->userId    = $userId;
        $this->messageId = $messageId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastWith()
    {
        return [
            'user_id'    => $this->userId,
            'message_id' => $this->messageId,
        ];
    }
}