<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'progress',
        'user_id',
        'file',
        'google_event_id',
    ];

    
    public function users() {
        return $this->belongsToMany(User::class);
    }
}
