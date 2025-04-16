@php
    $title = 'update';
    $css = 'task';
@endphp

@extends('header')

@section('content')
    <h1>Update Task</h1>
    <form action="{{ route('updateTask', $task->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="task_id" value="{{ $task->id }}">

        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ $task->title }}" required>
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ $task->description }}</textarea>
        </div>

        <div class="input-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $task->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="overdue" {{ $task->status == 'overdue' ? 'selected' : '' }}>Overdue</option> 
            </select>
        </div>

        <div class="input-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" value="{{ $task->due_date }}" required>
        </div>

        <div class="input-group">
            <label for="progress">Progress</label>
            <input type="number" id="progress" name="progress" value="{{ $task->progress }}" min="0" max="100" step="1" required oninput="updateProgressBar()">
            <div class="progress-bar">
                <div class="progress-fill" id="progressBarFill" style="width: {{ $task->progress }}%;"></div>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit">Update Task</button>
        </div>
    </form>

    <script>
        function updateProgressBar() {
            const progressValue = document.getElementById('progress').value;
            const progressBarFill = document.getElementById('progressBarFill');
            progressBarFill.style.width = progressValue + '%';
        }
    </script>
@endsection
