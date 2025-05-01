<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $taskId,public array $affectedUserIds,public bool $isAccessRemoval = false){}

    public function broadcastOn()
    {
        $channels = [];
        
        foreach ($this->affectedUserIds as $userId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$userId);
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->taskId,
            'action' => 'delete',
        ];
    }
}