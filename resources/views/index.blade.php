@php
    $title = 'Home';
    $css = 'index';
@endphp
@vite(['resources/js/app.js'])
@extends('header')

@section('content')
    @guest
        <h1>You must Login</h1>
    @endguest
    @auth
        <section class="center">
            <div class="box" id="tasks-container">
                @if ($tasks->isEmpty())
                    <h1 id="no-tasks-message">No tasks available. Please create a new task.</h1>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Due Date</th>
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                <tr id="task-{{ $task->id }}">
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
                                            {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: {{ $task->progress }}%;"></div>
                                        </div>
                                    </td>
                                    <td>{{ $task->due_date }}</td>
                                    <td>
                                        @if ($task->file)
                                            <a href="{{ route('download', $task->id) }}" class="show-more-btn" style="text-decoration: none;">
                                                Download
                                            </a>
                                        @else
                                            No File
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('updateTaskPage', $task->id) }}">
                                            <button type="button">Edit</button>
                                        </a>
                                        @if ($id == $task->user_id)
                                            <form action="{{ route('delete', $task->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

        <div id="description-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <p id="modal-description"></p>
            </div>
        </div>

        <script>
            setTimeout(() => {
                function handleTaskEvent(event) {
                    const task = event;
                    const action = event.action;
                    const container = document.getElementById('tasks-container');
                    const noTasksMessage = document.getElementById('no-tasks-message');


                    if (action === 'delete') {
                        const rowToRemove = document.getElementById(`task-${task.id}`);

                        if (rowToRemove) {
                            rowToRemove.remove();

                            const remainingTasks = document.querySelectorAll('tbody tr');

                            if (remainingTasks.length === 0) {
                                if (!noTasksMessage) {
                                    const newMessage = document.createElement('h1');
                                    newMessage.id = 'no-tasks-message';
                                    newMessage.textContent = 'No tasks available. Please create a new task.';

                                    const existingTable = container.querySelector('table');
                                    if (existingTable) {
                                        container.removeChild(existingTable);
                                    }

                                    container.appendChild(newMessage);
                                } else {
                                    noTasksMessage.style.display = 'block';

                                    const existingTable = container.querySelector('table');
                                    if (existingTable) {
                                        container.removeChild(existingTable);
                                    }
                                }
                            }
                        }
                        return;
                    }

                    if (noTasksMessage) {
                        noTasksMessage.style.display = 'none';
                    }

                    let table = container.querySelector('table');
                    let tableBody;

                    if (!table) {
                        table = document.createElement('table');
                        table.innerHTML = `
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
                        container.appendChild(table);
                    }

                    tableBody = table.querySelector('tbody');

                    const safeDescription = task.description ?
                        task.description.replace(/"/g, '&quot;').replace(/`/g, '\\`').replace(/\\/g, '\\\\') : '';

                    const taskStatus = (task.status || 'pending').toLowerCase();
                    const formattedStatus = (task.status || 'pending')
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, c => c.toUpperCase());

                    const rowHTML = `
            <td>${task.title || ''}</td>
            <td>
                ${task.description ?
                `<button class="show-more-btn" onclick="showDescription(\`${safeDescription}\`)">View</button>` :
                '<span>No description</span>'}
            </td>
            <td>
                <span class="status status-${taskStatus}">
                    ${formattedStatus}
                </span>
            </td>
            <td>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${task.progress || 0}%"></div>
                </div>
            </td>
            <td>${task.due_date || 'N/A'}</td>
            <td>
                ${task.file ? 
                    `<a href="/download/${task.id}" class="show-more-btn" style="text-decoration: none;">Download</a>` : 
                    '<span>No File</span>'}
            </td>
            <td>
                <a href="/tasks/${task.id}/edit"><button type="button">Edit</button></a>
                ${task.user_id == userId ?
                `<form action="/delete/${task.id}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>` : ''}
            </td>
        `;

                    let existingRow = document.getElementById(`task-${task.id}`);
                    if (existingRow) {
                        existingRow.innerHTML = rowHTML;
                    } else {
                        const newRow = document.createElement('tr');
                        newRow.id = `task-${task.id}`;
                        newRow.innerHTML = rowHTML;
                        tableBody.appendChild(newRow);
                    }
                }

                const userId = {{ $id }};

                window.Echo.private(`App.Models.User.${userId}`)
                    .listen('TaskCreated', (e) => {
                        handleTaskEvent(e);
                    })
                    .listen('TaskUpdated', (e) => {
                        handleTaskEvent(e);
                    })
                    .listen('TaskDeleted', (e) => {
                        handleTaskEvent(e);
                    });

            }, 2000);


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
