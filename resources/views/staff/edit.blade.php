<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Staff - Integrated School Management System</title>
</head>

<body>

    <h1>Edit Staff Member</h1>

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

    <form
        action="{{ route('staff.update', $staff) }}"
        method="POST"
    >

        @csrf
        @method('PUT')

        <p>
            <label>Employee Number</label><br>

            <input
                type="text"
                name="employee_number"
                value="{{ old('employee_number', $staff->employee_number) }}"
                required
            >
        </p>

        <p>
            <label>First Name</label><br>

            <input
                type="text"
                name="first_name"
                value="{{ old('first_name', $staff->first_name) }}"
                required
            >
        </p>

        <p>
            <label>Middle Name</label><br>

            <input
                type="text"
                name="middle_name"
                value="{{ old('middle_name', $staff->middle_name) }}"
            >
        </p>

        <p>
            <label>Last Name</label><br>

            <input
                type="text"
                name="last_name"
                value="{{ old('last_name', $staff->last_name) }}"
                required
            >
        </p>

        <p>
            <label>Gender</label><br>

            <select name="gender">

                <option value="">Select Gender</option>

                <option
                    value="Male"
                    {{ $staff->gender === 'Male' ? 'selected' : '' }}
                >
                    Male
                </option>

                <option
                    value="Female"
                    {{ $staff->gender === 'Female' ? 'selected' : '' }}
                >
                    Female
                </option>

            </select>

        </p>

        <p>
            <label>Date of Birth</label><br>

            <input
                type="date"
                name="date_of_birth"
                value="{{ old(
                    'date_of_birth',
                    $staff->date_of_birth?->format('Y-m-d')
                ) }}"
            >
        </p>

        <p>
            <label>Phone</label><br>

            <input
                type="text"
                name="phone"
                value="{{ old('phone', $staff->phone) }}"
            >
        </p>

        <p>
            <label>Email</label><br>

            <input
                type="email"
                name="email"
                value="{{ old('email', $staff->email) }}"
            >
        </p>

        <p>
            <label>Position</label><br>

            <input
                type="text"
                name="position"
                value="{{ old('position', $staff->position) }}"
                required
            >
        </p>

        <p>
            <label>Department</label><br>

            <input
                type="text"
                name="department"
                value="{{ old('department', $staff->department) }}"
            >
        </p>

        <p>
            <label>Status</label><br>

            <select name="status" required>

                <option
                    value="active"
                    {{ $staff->status === 'active' ? 'selected' : '' }}
                >
                    Active
                </option>

                <option
                    value="inactive"
                    {{ $staff->status === 'inactive' ? 'selected' : '' }}
                >
                    Inactive
                </option>

            </select>

        </p>

        <button type="submit">
            Update Staff
        </button>

    </form>

</body>
</html>