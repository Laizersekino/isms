<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Student - Integrated School Management System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }

        .container {
            max-width: 700px;
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
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, select, textarea {
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

    <h1>Edit Student</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('students.update', $student) }}">

        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Admission Number</label>
            <input type="text" name="admission_number"
                   value="{{ old('admission_number', $student->admission_number) }}"
                   required>
        </div>

        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name"
                   value="{{ old('first_name', $student->first_name) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name"
                   value="{{ old('middle_name', $student->middle_name) }}">
        </div>

        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name"
                   value="{{ old('last_name', $student->last_name) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth"
                   value="{{ old('date_of_birth', $student->date_of_birth) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="Male" {{ $student->gender == 'Male' ? 'selected' : '' }}>
                    Male
                </option>

                <option value="Female" {{ $student->gender == 'Female' ? 'selected' : '' }}>
                    Female
                </option>
            </select>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone"
                   value="{{ old('phone', $student->phone) }}">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                   value="{{ old('email', $student->email) }}">
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address">{{ old('address', $student->address) }}</textarea>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="Active" {{ $student->status == 'Active' ? 'selected' : '' }}>
                    Active
                </option>

                <option value="Inactive" {{ $student->status == 'Inactive' ? 'selected' : '' }}>
                    Inactive
                </option>
            </select>
        </div>

        <button type="submit">Update Student</button>

    </form>

</div>

</body>
</html>