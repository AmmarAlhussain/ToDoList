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
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\EventDateTime;
use Log;

class TaskController extends Controller
{

    public function index(Request $request)
    {
        $tasks = Task::where('user_id', $request->user()->id)
        ->orWhereHas('users', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
        ->get();
    
    return response()->json($tasks);
    }

    public function store(Request $request)
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

        if ($request->hasFile('file')) {
            $file = $request->file('file')->store('tasks', 's3');
            Storage::disk('s3')->setVisibility($file, 'public');
        }

        $dueDate = Carbon::parse($validated['due_date']);
        $status = $validated['status'];
        $progress = $validated['progress'];

        if ($dueDate->isPast() && $status !== 'completed') {
            $status = 'overdue';
        }


        if ($progress == 100 && $status !== 'completed') {
            $status = 'completed';
        }


        if ($status === 'completed' && $progress !== 100) {
            $progress = 100;
        }

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $status,
            'due_date' => $validated['due_date'],
            'user_id' => Auth::id(),
            'progress' => $progress,
            'file' => $file
        ]);

        if (!empty($validated['names'])) {
            $task->users()->attach($validated['names']);
        }

        $user = Auth::user();

        if ($user->google_token) {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'access_token' => $user->google_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_in,
                'created' => $user->google_token_created_at->timestamp,
            ]);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                $newToken = $client->getAccessToken();
                $user->google_token = $newToken['access_token'];
                $user->google_token_expires_in = $newToken['expires_in'];
                $user->google_token_created_at = now();
                $user->save();
            }

            $calendar = new GoogleCalendar($client);

            $event = new \Google\Service\Calendar\Event([
                'summary' => $task->title,
                'start' => [
                    'dateTime' => Carbon::parse($task->due_date)->setTime(8, 0)->toRfc3339String(),
                    'timeZone' => 'Asia/Riyadh',
                ],
                'end' => [
                    'dateTime' => Carbon::parse($task->due_date)->setTime(17, 0)->toRfc3339String(),
                    'timeZone' => 'Asia/Riyadh',
                ],
            ]);

            if (!empty($validated['names'])) {
                $attendees = User::whereIn('id', $validated['names'])
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->map(fn($email) => ['email' => $email])
                    ->values()
                    ->all();

                $event->setAttendees($attendees);
            }

            $createdEvent = $calendar->events->insert('primary', $event);
            $task->google_event_id = $createdEvent->id;
            $task->save();
        }

        $group = $task->users;
        foreach ($group as $user) {
            Mail::to($user->email)->send(new UpdateInfo(
                "A new task '{$task->title}' has been assigned to you. Status: {$task->status}, Progress: {$task->progress}%",
                "New Task Assigned: {$task->title}"
            ));
        }

        $owner = User::find($task->user_id);
        Mail::to($owner->email)->send(new UpdateInfo(
            "Your task '{$task->title}' has been created successfully. Status: {$task->status}, Progress: {$task->progress}%",
            "Task Created: {$task->title}"
        ));
        event(new TaskCreated($task));

        return response()->json($task, 201);
    }

    public function show(Request $request, $id)
    {
        $task = Task::where('user_id', $request->user()->id)->find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json($task);
    }

    public function remove(Request $request, $id)
    {
        $task = Task::findOrFail($id);
    
        if ($task->user_id != Auth::id()) {
            abort(403); 
        }
    
        $user = Auth::user();
    
        if ($task->google_event_id && $user->google_token) {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'access_token' => $user->google_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_in,
                'created' => $user->google_token_created_at->timestamp,
            ]);
    
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                $newToken = $client->getAccessToken();
                $user->google_token = $newToken['access_token'];
                $user->google_token_expires_in = $newToken['expires_in'];
                $user->google_token_created_at = now();
                $user->save();
            }
    
            $calendar = new GoogleCalendar($client);
    
            try {
                $calendar->events->delete('primary', $task->google_event_id);
            } catch (\Exception $e) {}
        }
    
        $affectedUserIds = collect([$task->user_id]);
        $sharedUserIds = $task->users()->pluck('user_id')->toArray();
        $affectedUserIds = $affectedUserIds->merge($sharedUserIds)->unique()->values()->toArray();
    
        $task->delete();
    
        event(new TaskDeleted($task->id, $affectedUserIds));
    
        return response()->json(['message' => 'Task deleted successfully']);
    }



    public function modify(Request $request, $id)
    {
        $task = Task::findOrFail($id);
    
        if ($task->user_id != Auth::id() && !$task->users()->where('user_id', Auth::id())->exists()) {
            abort(403, 'Unauthorized action.');
        }
    
        $validated = $request->validate([
            'title' => $task->user_id == Auth::id() ? 'required|string|max:255' : 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => $task->user_id == Auth::id() ? 'required|date|after_or_equal:today' : 'nullable|date|after_or_equal:today',
            'names' => 'array',
            'names.*' => 'exists:users,id',
            'file' => 'file|nullable|max:5120',
            'progress' => 'required|integer|between:0,100',
        ]);
    
        $file = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file')->store('tasks', 's3');
            Storage::disk('s3')->setVisibility($file, 'public');
        }
    
        $dueDate = isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : Carbon::parse($task->due_date);
        $status = $validated['status'];
        $progress = $validated['progress'];
    
        
        if ($dueDate->isPast() && $status !== 'completed') {
            $status = 'overdue';
        }
    
        if ($progress == 100 && $status !== 'completed') {
            $status = 'completed';
        }
    
        if ($status === 'completed' && $progress !== 100) {
            $progress = 100;
        }
    
        
        $previousUserIds = $task->users()->pluck('user_id')->toArray();
    
        
        $task->update([
            'title' => $validated['title'] ?? $task->title,
            'description' => $validated['description'] ?? $task->description,
            'status' => $status,
            'due_date' => $validated['due_date'] ?? $task->due_date,
            'progress' => $progress,
            'file' => $file ?? $task->file,
        ]);
    
        
        if ($task->user_id == Auth::id()) {
            $task->users()->detach();
            if (!empty($validated['names'])) {
                $task->users()->attach($validated['names']);
            }
        }
    
        
        $newUserIds = $task->users()->pluck('user_id')->toArray();
        $removedUserIds = array_diff($previousUserIds, $newUserIds);
    
        foreach ($removedUserIds as $userId) {
            broadcast(new TaskDeleted($task->id, [$userId], true));
            $removedUser = User::find($userId);
            if ($removedUser) {
                Log::info('Sending email to: ' . $removedUser->email);
                Mail::to($removedUser->email)->send(new UpdateInfo(
                    "You have been removed from the task '{$task->title}'.",
                    "Removed from Task: {$task->title}"
                ));
            } else {
                Log::warning('User not found for ID: ' . $userId);
            }
        }
    
        
        $group = $task->users;
        foreach ($group as $user) {
            Mail::to($user->email)->send(new UpdateInfo(
                "The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%",
                "Task Updated: {$task->title}"
            ));
        }
    
        
        Mail::to(User::find($task->user_id)->email)->send(new UpdateInfo(
            "The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%",
            "Task Updated: {$task->title}"
        ));
    
        
        $isOwner = $task->user_id === Auth::id();
        if ($isOwner && $task->google_event_id) {
            $user = Auth::user();
    
            if ($user->google_token) {
                $client = new GoogleClient();
                $client->setClientId(config('services.google.client_id'));
                $client->setClientSecret(config('services.google.client_secret'));
                $client->setAccessToken([
                    'access_token' => $user->google_token,
                    'refresh_token' => $user->google_refresh_token,
                    'expires_in' => $user->google_token_expires_in,
                    'created' => $user->google_token_created_at->timestamp,
                ]);
    
                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                    $newToken = $client->getAccessToken();
                    $user->google_token = $newToken['access_token'];
                    $user->google_token_expires_in = $newToken['expires_in'];
                    $user->google_token_created_at = now();
                    $user->save();
                }
    
                $calendar = new GoogleCalendar($client);
    
                try {
                    $event = $calendar->events->get('primary', $task->google_event_id);
    
                    $event->setSummary($task->title);
                    $event->setStart(new EventDateTime([
                        'dateTime' => $dueDate->copy()->setTime(8, 0)->toRfc3339String(),
                        'timeZone' => 'Asia/Riyadh',
                    ]));
                    $event->setEnd(new EventDateTime([
                        'dateTime' => $dueDate->copy()->setTime(17, 0)->toRfc3339String(),
                        'timeZone' => 'Asia/Riyadh',
                    ]));
    
                    if ($task->users()->exists()) {
                        $attendees = $task->users()->pluck('email')->map(fn($email) => ['email' => $email])->toArray();
                        $event->setAttendees($attendees);
                    }
    
                    $calendar->events->update('primary', $task->google_event_id, $event);
                } catch (\Exception $e) {
                    Log::warning('Failed to update Google Calendar event: ' . $e->getMessage());
                }
            }
        }
    
        event(new TaskUpdated($task));
    
        return response()->json(['message' => 'Task updated successfully']);
    }
    


    public function addTaskPage()
    {
        $users = User::where('id', '!=', Auth::id())->pluck("name", "id");
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


        if ($request->hasFile('file')) {
            $file = $request->file('file')->store('tasks', 's3');
            Storage::disk('s3')->setVisibility($file, 'public');
        }

        $dueDate = Carbon::parse($validated['due_date']);
        $status = $validated['status'];
        $progress = $validated['progress'];

        if ($dueDate->isPast() && $status !== 'completed') {
            $status = 'overdue';
        }


        if ($progress == 100 && $status !== 'completed') {
            $status = 'completed';
        }


        if ($status === 'completed' && $progress !== 100) {
            $progress = 100;
        }

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $status,
            'due_date' => $validated['due_date'],
            'user_id' => Auth::id(),
            'progress' => $progress,
            'file' => $file
        ]);

        if (!empty($validated['names'])) {
            $task->users()->attach($validated['names']);
        }

        $user = Auth::user();

        if ($user->google_token) {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'access_token' => $user->google_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_in,
                'created' => $user->google_token_created_at->timestamp,
            ]);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                $newToken = $client->getAccessToken();
                $user->google_token = $newToken['access_token'];
                $user->google_token_expires_in = $newToken['expires_in'];
                $user->google_token_created_at = now();
                $user->save();
            }

            $calendar = new GoogleCalendar($client);

            $event = new \Google\Service\Calendar\Event([
                'summary' => $task->title,
                'start' => [
                    'dateTime' => Carbon::parse($task->due_date)->setTime(8, 0)->toRfc3339String(),
                    'timeZone' => 'Asia/Riyadh',
                ],
                'end' => [
                    'dateTime' => Carbon::parse($task->due_date)->setTime(17, 0)->toRfc3339String(),
                    'timeZone' => 'Asia/Riyadh',
                ],
            ]);

            if (!empty($validated['names'])) {
                $attendees = User::whereIn('id', $validated['names'])
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->map(fn($email) => ['email' => $email])
                    ->values()
                    ->all();

                $event->setAttendees($attendees);
            }

            $createdEvent = $calendar->events->insert('primary', $event);
            $task->google_event_id = $createdEvent->id;
            $task->save();
        }

            $group = $task->users;
            foreach ($group as $user) {
                Mail::to($user->email)->send(new UpdateInfo(
                    "A new task '{$task->title}' has been assigned to you. Status: {$task->status}, Progress: {$task->progress}%",
                    "New Task Assigned: {$task->title}"
                ));
            }

            $owner = User::find($task->user_id);
            Mail::to($owner->email)->send(new UpdateInfo(
                "Your task '{$task->title}' has been created successfully. Status: {$task->status}, Progress: {$task->progress}%",
                "Task Created: {$task->title}"
            ));

        event(new TaskCreated($task));
        return redirect()->route('home');
    }

    public function updateTaskPage(Task $task)
    {
        if ($task->user_id != Auth::id() && !$task->users()->where('user_id', Auth::id())->exists()) {
            abort(403);
        }
        $users = "null";
        if ($task->user_id == Auth::id()) {
            $users = User::where('id', '!=', Auth::id())->pluck("name", "id");
        }
        return view('updateTask', compact('task', 'users'));
    }

    public function download(Task $task)
    {
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
            'title' => $task->user_id == Auth::id() ? 'required|string|max:255' : 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
            'due_date' => $task->user_id == Auth::id() ? 'required|date|after_or_equal:today' : 'nullable|date|after_or_equal:today',
            'names' => 'array',
            'names.*' => 'exists:users,id',
            'file' => 'file|nullable|max:5120',
            'progress' => 'required|integer|between:0,100',
        ]);

        $file = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file')->store('tasks', 's3');
            Storage::disk('s3')->setVisibility($file, 'public');
        }

        $dueDate = Carbon::parse($validated['due_date']);
        $status = $validated['status'];
        $progress = $validated['progress'];

        if ($dueDate->isPast() && $status !== 'completed') {
            $status = 'overdue';
        }


        if ($progress == 100 && $status !== 'completed') {
            $status = 'completed';
        }


        if ($status === 'completed' && $progress !== 100) {
            $progress = 100;
        }

        $previousUserIds = $task->users()->pluck('user_id')->toArray();
        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $status,
            'due_date' => $validated['due_date'],
            'progress' => $progress,
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
                $removedUser = User::find($userId);
                if ($removedUser) {
                    Log::info('Sending email to: ' . $removedUser->email);
                    Mail::to($removedUser->email)->send(new UpdateInfo(
                        "You have been removed from the task '{$task->title}'.",
                        "Removed from Task: {$task->title}"
                    ));
                } else {
                    Log::warning('User not found for ID: ' . $userId);
                }
            }
        }

        $group = $task->users;
        foreach ($group as $user) {
            Mail::to($user->email)->send(new UpdateInfo("The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%", "Task Updated: {$task->title}"));
        }
        Mail::to(User::find($task->user_id)->email)->send(new UpdateInfo("The task '{$task->title}' has been updated. Status: {$task->status}, Progress: {$task->progress}%", "Task Updated: {$task->title}"));


        $isOwner = $task->user_id === Auth::id();
        if ($isOwner && $task->google_event_id) {
            $user = Auth::user();

            if ($user->google_token) {
                $client = new GoogleClient();
                $client->setClientId(config('services.google.client_id'));
                $client->setClientSecret(config('services.google.client_secret'));
                $client->setAccessToken([
                    'access_token' => $user->google_token,
                    'refresh_token' => $user->google_refresh_token,
                    'expires_in' => $user->google_token_expires_in,
                    'created' => $user->google_token_created_at->timestamp,
                ]);

                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                    $newToken = $client->getAccessToken();
                    $user->google_token = $newToken['access_token'];
                    $user->google_token_expires_in = $newToken['expires_in'];
                    $user->google_token_created_at = now();
                    $user->save();
                }

                $calendar = new GoogleCalendar($client);

                try {
                    $event = $calendar->events->get('primary', $task->google_event_id);

                    $event->setSummary($task->title);
                    $start = new EventDateTime([
                        'dateTime' => Carbon::parse($task->due_date)->setTime(8, 0)->toRfc3339String(),
                        'timeZone' => 'Asia/Riyadh',
                    ]);

                    $end = new EventDateTime([
                        'dateTime' => Carbon::parse($task->due_date)->setTime(17, 0)->toRfc3339String(),
                        'timeZone' => 'Asia/Riyadh',
                    ]);

                    $event->setStart($start);
                    $event->setEnd($end);

                    if ($task->users()->exists()) {
                        $attendees = $task->users()->whereNotNull('email')->pluck('email')->map(fn($email) => ['email' => $email])->values()->all();
                        $event->setAttendees($attendees);
                    }

                    $calendar->events->update('primary', $task->google_event_id, $event);
                } catch (\Exception $e) {
                }
            }
        }


        event(new TaskUpdated($task));

        return redirect()->route('home');
    }


    public function destroy(Task $task)
    {

        if ($task->user_id != Auth::id()) {
            abort(403);
        }

        $user = Auth::user();

        if ($task->google_event_id && $user->google_token) {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'access_token' => $user->google_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_in,
                'created' => $user->google_token_created_at->timestamp,
            ]);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                $newToken = $client->getAccessToken();
                $user->google_token = $newToken['access_token'];
                $user->google_token_expires_in = $newToken['expires_in'];
                $user->google_token_created_at = now();
                $user->save();
            }

            $calendar = new GoogleCalendar($client);

            try {
                $calendar->events->delete('primary', $task->google_event_id);
            } catch (\Exception $e) {
            }
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
