<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" type='text/css'>
    <title>{{ $title }}</title>
    <style>
        * {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>

<body>
    <div class="body">
        <header>
            <img src="{{asset('asset/logo.png')}}" alt="logo">
        </header>
        <main class="main">
            {{ $slot }}
        </main>
    </div>

</body>

</html>
