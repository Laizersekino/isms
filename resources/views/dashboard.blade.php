<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Integrated School Management System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            min-height: 100vh;
        }

        .navbar {
            background: #1f2937;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 22px;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .navbar-link {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .logout-button {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .welcome {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .welcome h2 {
            margin-bottom: 10px;
            color: #1f2937;
        }

        .welcome p {
            color: #6b7280;
        }
    </style>
</head>

<body>

<nav class="navbar">

    <h1>Integrated School Management System</h1>

    <div class="navbar-links">
        @php($user = auth()->user())

        @if(
            $user->hasRole('Student') ||
            $user->hasRole('Parent') ||
            $user->hasPermission('student.portal') ||
            $user->hasPermission('parent.portal') ||
            $user->hasPermission('students.view') ||
            $user->hasPermission('reports.view') ||
            $user->hasPermission('marks.view')
        )
            <a href="{{ route('student-results.index') }}" class="navbar-link">
                Student Results
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="logout-button">
                Logout
            </button>
        </form>
    </div>

</nav>

<div class="container">

    <div class="welcome">

        <h2>Welcome, {{ auth()->user()->name }}</h2>
        @if (auth()->user()->hasPermission('students.view'))
    <p>Students</p>
@endif
        <p>
    Role:
    {{ auth()->user()->roles->first()->name ?? 'No role assigned' }}
</p>
<h3>Your Permissions</h3>

<ul>
    @foreach (auth()->user()->roles->flatMap->permissions as $permission)
        <li>{{ $permission->name }}</li>
    @endforeach
</ul>

        <p>
            You are successfully logged in to the
            Integrated School Management System.
        </p>

    </div>

</div>

</body>
</html>