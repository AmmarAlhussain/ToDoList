<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function indexPage() {
        $tasks = collect();
        $id = "null";
        if (Auth::check()) {
            $owner = Task::where('user_id', Auth::id())->get();

            $member = Task::whereHas('users', function ($query) {
                    $query->where('users.id', Auth::id());
                })->get();
                
            $tasks =  $owner->merge($member)->sortBy('due_date');


            foreach ($tasks as $task) {
                $dueDate = Carbon::parse($task->due_date);

                if ($task->status !== 'completed' && $dueDate->isPast()) {
                    $task->status = 'overdue';
                }

                $task->save();
            }
            $id = Auth::id();
        }

        return view("index", compact('tasks',"id"));
    }
}
