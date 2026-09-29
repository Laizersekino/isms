<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff - Integrated School Management System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        h1 {
            margin-bottom: 20px;
        }

        a, button {
            padding: 8px 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .add-btn {
            background: #198754;
            color: white;
        }

        .edit-btn {
            background: #ffc107;
            color: black;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 10px;
            margin: 15px 0;
        }

        form {
            display: inline;
        }
    </style>
</head>

<body>

    <h1>Staff Management</h1>

    <a href="{{ route('dashboard') }}">Dashboard</a>

    <a href="{{ route('staff.create') }}" class="add-btn">
        Add Staff
    </a>

    @if(session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if($staff->count())

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Number</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                @foreach($staff as $member)

                    <tr>

                        <td>{{ $member->id }}</td>

                        <td>{{ $member->employee_number }}</td>

                        <td>
                            {{ $member->first_name }}
                            {{ $member->middle_name }}
                            {{ $member->last_name }}
                        </td>

                        <td>{{ $member->gender }}</td>

                        <td>{{ $member->phone }}</td>

                        <td>{{ $member->email }}</td>

                        <td>{{ $member->position }}</td>

                        <td>{{ $member->department }}</td>

                        <td>{{ $member->status }}</td>

                        <td>

                            <a
                                href="{{ route('staff.edit', $member) }}"
                                class="edit-btn"
                            >
                                Edit
                            </a>

                            <form
                                action="{{ route('staff.destroy', $member) }}"
                                method="POST"
                            >

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this staff member?')"
                                >
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <p>No staff records found.</p>

    @endif

</body>
</html>