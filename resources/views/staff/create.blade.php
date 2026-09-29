<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Staff - Integrated School Management System</title>
</head>

<body>

    <h1>Add Staff Member</h1>

    <a href="{{ route('staff.index') }}">Back to Staff</a>

    @if($errors->any())

        <div>
            <strong>Please correct the following errors:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif

    <form action="{{ route('staff.store') }}" method="POST">

        @csrf

        <p>
            <label>Employee Number</label><br>
            <input
                type="text"
                name="employee_number"
                value="{{ old('employee_number') }}"
                required
            >
        </p>

        <p>
            <label>First Name</label><br>
            <input
                type="text"
                name="first_name"
                value="{{ old('first_name') }}"
                required
            >
        </p>

        <p>
            <label>Middle Name</label><br>
            <input
                type="text"
                name="middle_name"
                value="{{ old('middle_name') }}"
            >
        </p>

        <p>
            <label>Last Name</label><br>
            <input
                type="text"
                name="last_name"
                value="{{ old('last_name') }}"
                required
            >
        </p>

        <p>
            <label>Gender</label><br>

            <select name="gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </p>

        <p>
            <label>Date of Birth</label><br>

            <input
                type="date"
                name="date_of_birth"
                value="{{ old('date_of_birth') }}"
            >
        </p>

        <p>
            <label>Phone</label><br>

            <input
                type="text"
                name="phone"
                value="{{ old('phone') }}"
            >
        </p>

        <p>
            <label>Email</label><br>

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
            >
        </p>

        <p>
            <label>Position</label><br>

            <input
                type="text"
                name="position"
                value="{{ old('position') }}"
                required
            >
        </p>

        <p>
            <label>Department</label><br>

            <input
                type="text"
                name="department"
                value="{{ old('department') }}"
            >
        </p>

        <p>
            <label>Status</label><br>

            <select name="status" required>

                <option value="active">Active</option>
                <option value="inactive">Inactive</option>

            </select>

        </p>

        <button type="submit">
            Save Staff
        </button>

    </form>

</body>
</html>