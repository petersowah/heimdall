<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Heimdall</title>
    <link rel="stylesheet" href="{{ asset('vendor/heimdall/app.css') }}">
</head>
<body>
    <div
        id="heimdall-app"
        data-base-url="{{ url(config('heimdall.path', 'heimdall')) }}"
        data-user="{{ json_encode(['name' => auth()->user()?->name, 'email' => auth()->user()?->email]) }}"
    ></div>
    <script src="{{ asset('vendor/heimdall/app.js') }}"></script>
</body>
</html>
