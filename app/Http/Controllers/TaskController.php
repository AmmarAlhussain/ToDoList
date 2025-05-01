<?php

namespace App\Http\Controllers;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\UpdateInfo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Events\TaskDeleted;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function addTaskPage()
    {
        $users = User::where('id','!=',Auth::id())->pluck("name","id");
        return view('addTask', compact('users'));
    }

    public function addTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => 'required|date|after_or_equal:today',
            'names' => 'array',
            'names.*' => 'exists:users,id',
            'progress' => 'required|integer|between:0,100',
            'file' => 'file|nullable|max:5120',
        ]);

        $file = null;


        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file')->store('tasks', 's3');
            Storage::disk('s3')->setVisibility($file, 'public');
        }

        $dueDate = Carbon::parse($validated['due_date']);
        $status = $validated['status'];
        
        
        if ($dueDate->isPast() && $status !== 'completed') {
            $status = 'overdue';
        }
        
        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $status,
            'due_date' => $validated['due_date'],
            'user_id' => Auth::id(),
            'progress' => $validated['progress'],
            'file' => $file
        ]);
        
        if (!empty($validated['names'])) {
            $task->users()->attach($validated['names']);
        }
        event(new TaskCreated($task));
        return redirect()->route('home'); 
    }

    public function updateTaskPage(Task $task)
    {
        if ($task->user_id != Auth::id() && !$task->users()->where('user_id', Auth::id())->exists()) {
            abort(403);
        }
        $users ="null";
        if ($task->user_id == Auth::id()) { 
            $users = User::where('id','!=',Auth::id())->pluck("name","id");
        }
        return view('updateTask', compact('task','users'));
    }

    public function download(Task $task)    {
        if ($task->user_id != Auth::id() && !$task->users()->where('user_id', Auth::id())->exists()) {
            abort(403);
        }

        if ($task->file && Storage::disk('s3')->exists($task->file)) {
            return Storage::disk('s3')->download($task->file);

        }
        abort(404);
    
    }

    public function updateTask(Request $request, Task $task)
    {
        if ($task->user_id != Auth::id() && !$task->users()->where('user_id', Auth::id())->exists()) {
            abort(403, 'Unauthorized action.');
        }        
    
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => 'required|date|after_or_equal:today',
            'names' => 'array',
            'names.*' => 'exists:users,id',
            'file' => 'file|nullable|max:5120',
            'progress' => 'required|integer|between:0,100',
        ]);

    $file = null;
    if ($request->hasFile('file') && $request->file('file')->isValid()) {
        $file = $request->file('file')->store('tasks', 's3');
        Storage::disk('s3')->setVisibility($file, 'public');
    }

    $dueDate = Carbon::parse($validated['due_date']);
    $status = $validated['status'];

    if ($dueDate->isPast() && $status !== 'completed') {
        $status = 'overdue';
    }
        
    $previousUserIds = $task->users()->pluck('user_id')->toArray();
    $task->update([
        'title' => $validated['title'],
        'description' => $validated['description'] ?? '',
        'status' => $status,
        'due_date' => $validated['due_date'],
        'progress' => $validated['progress'],
        'file' => $file
    ]);
    
    if ($task->user_id == Auth::id()) { 
        $task->users()->detach();
        if (!empty($validated['names'])) {
            $task->users()->attach($validated['names']);
        }
    }
    $newUserIds = $task->users()->pluck('user_id')->toArray();

    $removedUserIds = array_diff($previousUserIds, $newUserIds);

    if (!empty($removedUserIds)) {
        foreach ($removedUserIds as $userId) {
            broadcast(new TaskDeleted($task->id, [$userId], true));
        }
    }

    foreach ($removedUserIds as $userId) {
        $removedUser = User::find($userId);
        if ($removedUser) {
            Mail::to($removedUser->email)->send(new UpdateInfo(
                "You have been removed from the task '{$task->title}'.", 
                "Removed from Task: {$task->title}"
            ));
        }
    }
    
        $group = $task->users;
        foreach ($group as $user) {
            Mail::to($user->email)->send(new UpdateInfo("The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%", "Task Updated: {$task->title}"));
        }
        Mail::to(User::find($task->user_id)->email)->send(new UpdateInfo("The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%", "Task Updated: {$task->title}"));
        event(new TaskUpdated($task));

        return redirect()->route('home'); 
    }


    public function destroy(Task $task)
    {

        if ($task->user_id != Auth::id()) {
            abort(403);
        }
        
        
        $taskId = $task->id;
        
        
        $affectedUserIds = collect([$task->user_id]);
        $sharedUserIds = $task->users()->pluck('user_id')->toArray();
        $affectedUserIds = $affectedUserIds->merge($sharedUserIds)->unique()->values()->toArray();
        
        $task->delete();
        

        event(new TaskDeleted($taskId, $affectedUserIds));
        
        return redirect()->route('home');
    }
    
}
