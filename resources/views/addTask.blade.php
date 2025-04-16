@php
    $title = 'add';
    $css = 'task';
@endphp

@extends('header')

@section('content')
    <h1>Create New Task</h1>
    <form action="{{ route('addTask') }}" method="POST">
        @csrf
        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required placeholder="Enter task title">
            @error('title')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Enter task description"></textarea>
            @error('description')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="overdue">Overdue</option> 
            </select>
            @error('status')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" required min="{{ date('Y-m-d') }}">
            @error('due_date')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group">
            <label for="progress">Progress</label>
            <input type="range" id="progress" name="progress" min="0" max="100" value="0" required>
            <span id="progressValue">0%</span>
            @error('progress')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-footer">
            <button type="submit">Create Task</button>
        </div>
    </form>

    <script>
        const progress = document.getElementById('progress');
        const progressValue = document.getElementById('progressValue');

        progress.addEventListener('input', function (e) {
            progressValue.textContent = e.target.value + "%";
        });
        
        progressValue.textContent = progress.value + "%";
    </script>
@endsection
