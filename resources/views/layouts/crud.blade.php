<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'ISMS' }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        th {
            background: #f2f2f2;
        }

        input, select, textarea {
            padding: 8px;
            width: 350px;
            margin-top: 5px;
        }

        button, a {
            padding: 8px 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .success {
            background: #d1e7dd;
            padding: 10px;
            margin: 15px 0;
        }

        .danger {
            background: #dc3545;
            color: white;
        }

        .warning {
            background: #ffc107;
            color: black;
        }

        .primary {
            background: #198754;
            color: white;
        }
    </style>
</head>

<body>

    @if(session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div>
            <strong>Please correct these errors:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')

</body>
</html>