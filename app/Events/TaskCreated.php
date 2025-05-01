<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Models\Task;

class TaskCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public $task){}


    public function broadcastOn()
    {
        $users = collect([$this->task->user_id]); 
        
        if (method_exists($this->task, 'users')) {
            $sharedUserIds = $this->task->users()->pluck('user_id');
            $users = $users->merge($sharedUserIds);
        }
        
        $channels = [];
        
        foreach ($users as $userId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$userId);
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id ?? null,
            'title' => $this->task->title ?? '',
            'description' => $this->task->description ?? '',
            'status' => $this->task->status ?? '',
            'progress' => $this->task->progress ?? 0,
            'due_date' => $this->task->due_date ?? '',
            'user_id' => $this->task->user_id ?? 0,
            'action' => 'add',
        ];
    }
}