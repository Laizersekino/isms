<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - Integrated School Management System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 24px;
            color: #1f2937;
            font-size: 25px;
            text-align: center;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            color: #374151;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .error {
            margin-bottom: 16px;
            padding: 10px;
            border-radius: 6px;
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>
<body>
<main class="reset-container">
    <h1>Set your password</h1>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email Address</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus>

        <label for="password">New Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">

        <label for="password_confirmation">Confirm New Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">Set Password</button>
    </form>
</main>
</body>
</html>
