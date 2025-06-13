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

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // eager-load sender and replyTo sender
        $this->message = $message->load(['sender', 'replyTo.sender']);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    public function broadcastWith()
    {
        return [
            'id'                     => $this->message->id,
            'chat_id'                => $this->message->chat_id,
            'sender'                 => [
                'id'     => $this->message->sender->id,
                'name'   => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ],
            'type'                   => $this->message->type,
            'content'                => $this->message->content,
            'metadata'               => $this->message->metadata,
            'reply_to_message_id'    => $this->message->reply_to_message_id,
            'reply_to'               => $this->message->replyTo
                                        ? [
                                            'id'      => $this->message->replyTo->id,
                                            'sender'  => [
                                                'id'   => $this->message->replyTo->sender->id,
                                                'name' => $this->message->replyTo->sender->name,
                                            ],
                                            'content' => Str::limit($this->message->replyTo->content, 100),
                                          ]
                                        : null,
            'created_at'             => $this->message->created_at->toDateTimeString(),
        ];
    }
}