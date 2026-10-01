<?php

namespace App\Events;

use App\Models\ChatRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GroupRoomUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $room;

    public function __construct(ChatRoom $room)
    {
        $this->room = $room;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat-room.' . $this->room->id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->room->id,
            'name' => $this->room->name,
            'image_url' => $this->room->image ? asset($this->room->image) : null,
        ];
    }

    public function broadcastAs()
    {
        return 'group-room.updated';
    }
}
