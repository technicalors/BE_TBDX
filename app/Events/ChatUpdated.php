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
        $this->chat = $chat->load('participants:id,name,avatar');
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chat->id);
    }

    public function broadcastWith()
    {
        return [
            'id'           => $this->chat->id,
            'type'         => $this->chat->type,
            'name'         => $this->chat->name,
            'avatar'       => $this->chat->avatar,
            'participants' => $this->chat->participants->map(function($u) {
                                    return [
                                        'id'     => $u->id,
                                        'name'   => $u->name,
                                        'avatar' => $u->avatar,
                                    ];
                                }),
            'updated_at'   => $this->chat->updated_at->toDateTimeString(),
        ];
    }
}