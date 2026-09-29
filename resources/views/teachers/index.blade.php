<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teachers - Integrated School Management System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1300px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 5px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #1f2937;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .edit-button {
            background: #f59e0b;
            color: white;
            padding: 6px 10px;
            text-decoration: none;
            border-radius: 4px;
        }

        .delete-button {
            background: #dc2626;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="top-bar">

        <h1>Teachers</h1>

        <a href="{{ route('teachers.create') }}" class="add-button">
            Add Teacher
        </a>

    </div>

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    <table>

        <thead>
            <tr>
                <th>Employee Number</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Qualification</th>
                <th>Specialization</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($teachers as $teacher)

                <tr>

                    <td>{{ $teacher->employee_number }}</td>

                    <td>
                        {{ $teacher->first_name }}
                        {{ $teacher->middle_name }}
                        {{ $teacher->last_name }}
                    </td>

                    <td>{{ $teacher->gender }}</td>

                    <td>{{ $teacher->phone }}</td>

                    <td>{{ $teacher->email }}</td>

                    <td>{{ $teacher->qualification }}</td>

                    <td>{{ $teacher->specialization }}</td>

                    <td>{{ $teacher->status }}</td>

                    <td>

                        <a href="{{ route('teachers.edit', $teacher) }}"
                           class="edit-button">
                            Edit
                        </a>

                        <form method="POST"
                              action="{{ route('teachers.destroy', $teacher) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Are you sure you want to delete this teacher?');">

                            @csrf
                            @method('DELETE')

                            <button type="submit" class="delete-button">
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="9">
                        No teachers found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

</body>
</html>