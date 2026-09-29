<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Teacher - Integrated School Management System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Edit Teacher</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST"
          action="{{ route('teachers.update', $teacher) }}">

        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Employee Number</label>
            <input type="text"
                   name="employee_number"
                   value="{{ old('employee_number', $teacher->employee_number) }}"
                   required>
        </div>

        <div class="form-group">
            <label>First Name</label>
            <input type="text"
                   name="first_name"
                   value="{{ old('first_name', $teacher->first_name) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Middle Name</label>
            <input type="text"
                   name="middle_name"
                   value="{{ old('middle_name', $teacher->middle_name) }}">
        </div>

        <div class="form-group">
            <label>Last Name</label>
            <input type="text"
                   name="last_name"
                   value="{{ old('last_name', $teacher->last_name) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender">

                <option value="">Select Gender</option>

                <option value="Male"
                    {{ $teacher->gender == 'Male' ? 'selected' : '' }}>
                    Male
                </option>

                <option value="Female"
                    {{ $teacher->gender == 'Female' ? 'selected' : '' }}>
                    Female
                </option>

            </select>
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date"
                   name="date_of_birth"
                   value="{{ old('date_of_birth', $teacher->date_of_birth) }}">
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text"
                   name="phone"
                   value="{{ old('phone', $teacher->phone) }}">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email"
                   name="email"
                   value="{{ old('email', $teacher->email) }}">
        </div>

        <div class="form-group">
            <label>Qualification</label>
            <input type="text"
                   name="qualification"
                   value="{{ old('qualification', $teacher->qualification) }}">
        </div>

        <div class="form-group">
            <label>Specialization</label>
            <input type="text"
                   name="specialization"
                   value="{{ old('specialization', $teacher->specialization) }}">
        </div>

        <div class="form-group">
            <label>Status</label>

            <select name="status" required>

                <option value="active"
                    {{ $teacher->status == 'active' ? 'selected' : '' }}>
                    Active
                </option>

                <option value="inactive"
                    {{ $teacher->status == 'inactive' ? 'selected' : '' }}>
                    Inactive
                </option>

            </select>

        </div>

        <button type="submit">
            Update Teacher
        </button>

    </form>

</div>

</body>
</html>