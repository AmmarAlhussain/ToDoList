@php
    $title = 'update';
    $css = 'task';
@endphp

@extends('header')

@section('content')

    <h1>Update Task</h1>

    <form action="{{ route('updateTask', $task->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="task_id" value="{{ $task->id }}">

        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}" @if($task->user_id != Auth::id()) disabled @endif required>
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $task->description) }}</textarea>
        </div>

        <div class="input-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="overdue" {{ old('status', $task->status) == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
        </div>

        <div class="input-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $task->due_date) }}" @if($task->user_id != Auth::id()) disabled @endif  required>
        </div>
        @if ($users != "null")
        <div class="input-group">
            <select name="names[]" multiple style="height: 100px;">
                @foreach ( $users as $key => $value )
                <option value="{{$key}}">{{$value}}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="input-group">
            <input type="file" name="file">
        </div>

        <div class="input-group">
            <label for="progress">Progress</label>
            <input type="range" id="progress" name="progress" min="0" max="100" value="{{ old('progress', $task->progress) }}" required style="padding: 3px 0">
            <span id="progressValue">{{ old('progress', $task->progress) }}%</span>
        </div>

        <div class="form-footer">
            <button type="submit">Update Task</button>
        </div>
    </form>

    <script>
    const progress = document.getElementById('progress');
    const progressValue = document.getElementById('progressValue');
    const statusSelect = document.getElementById('status');
    const progressBarFill = document.getElementById('progressBarFill');

    function updateProgressDisplay(value) {
        progress.value = value;
        progressValue.textContent = value + "%"; 
    }

    progress.addEventListener('input', function (e) {
        updateProgressDisplay(e.target.value);
    });

    statusSelect.addEventListener('change', function () {
        if (this.value === 'completed') {
            updateProgressDisplay(100);
        } else {
            updateProgressDisplay(0);
        }
    });

    updateProgressDisplay(progress.value);
    </script>
@endsection
