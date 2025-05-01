@php
    $title = 'add';
    $css = 'task';
@endphp

@extends('header')

@section('content')
@if ($errors->any())
    <div style="color: red;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <h1>Create New Task</h1>

    <form action="{{ route('addTask') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required placeholder="Enter task title">
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Enter task description"></textarea>
        </div>

        <div class="input-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="input-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" required min="{{ date('Y-m-d') }}">
        </div>
        @if ($users != collect())
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
            <input type="range" id="progress" name="progress" min="0" max="100" value="0" required style="padding: 3px 0">
            <span id="progressValue">0%</span>
        </div>

        <div class="form-footer">
            <button type="submit">Create Task</button>
        </div>

    </form>

    <script>
    const progress = document.getElementById('progress');
    const progressValue = document.getElementById('progressValue');
    const statusSelect = document.getElementById('status');

    function updateProgressDisplay(value) {
        progress.value = value;
        progressValue.textContent = value + "%";
    }

    progress.addEventListener('input', function (e) {
        progressValue.textContent = e.target.value + "%";
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
