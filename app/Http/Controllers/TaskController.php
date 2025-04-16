<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function addTaskPage()
    {
        return view('addTask');
    }

    public function addTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => 'required|date|after_or_equal:today',
            'progress' => 'required|integer|between:0,100',
        ]);

        Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'],
            'due_date' => $validated['due_date'],
            'user_id' => Auth::id(),
            'progress' => $validated['progress']
        ]);

        return redirect()->route('home'); 
    }

    public function updateTaskPage(Task $task)
    {
        if ($task->user_id != Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('updateTask', compact('task'));
    }

    public function updateTask(Request $request, Task $task)
    {
        if ($task->user_id != Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => 'required|date|after_or_equal:today',
            'progress' => 'required|integer|between:0,100',
        ]);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'],
            'due_date' => $validated['due_date'],
            'progress' => $validated['progress']
        ]);

        return redirect()->route('home'); 
    }

    public function destroy(Task $task)
    {
        if ($task->user_id != Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $task->delete();

        return redirect()->route('home'); 
    }
}
