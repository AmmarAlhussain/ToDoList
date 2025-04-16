@php
    $title = 'Home';
    $css = 'index';
@endphp

@extends('header')

@section('content')
    @guest
        <h1>You must Login</h1>
    @endguest
    @auth
        @if ($tasks->isEmpty())
            <h1>No tasks available. Please create a new task.</h1>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>
                                @if (!empty($task->description))
                                    <button class="show-more-btn"
                                        onclick="showDescription(`{{ addslashes($task->description) }}`)">
                                        View
                                    </button>
                                @else
                                    <span>No description</span>
                                @endif
                            </td>
                            <td>
                                <span class="status status-{{ strtolower($task->status) }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: {{ $task->progress }}%;"></div>
                                </div>
                            </td>
                            <td>{{ $task->due_date }}</td>
                            <td>
                                <a href="{{ route('updateTaskPage', $task->id) }}">
                                    <button type="button">Edit</button>
                                </a>

                                <form action="{{ route('delete', $task->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div id="description-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <p id="modal-description"></p>
            </div>
        </div>

        <script>
            function showDescription(text) {
                document.getElementById('modal-description').textContent = text;
                document.getElementById('description-modal').style.display = 'flex';
            }

            function closeModal() {
                document.getElementById('description-modal').style.display = 'none';
            }

            window.onclick = function(event) {
                const modal = document.getElementById('description-modal');
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
    @endauth
@endsection
