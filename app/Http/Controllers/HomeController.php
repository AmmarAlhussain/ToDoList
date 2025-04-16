<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function indexPage() {
        $tasks = collect();

        if (Auth::check()) {
            $tasks = Task::where('user_id', Auth::id())
                ->orderBy('due_date', 'asc')
                ->get();

            foreach ($tasks as $task) {
                $dueDate = Carbon::parse($task->due_date);

                if ($task->status !== 'completed' && $dueDate->isPast()) {
                    $task->status = 'overdue';
                }

                if ($task->progress == 100 && $task->status !== 'completed') {
                    $task->status = 'completed';
                }

                $task->save();
            }
        }

        return view("index", compact('tasks'));
    }
}
