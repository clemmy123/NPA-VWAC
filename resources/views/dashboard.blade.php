<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NPA VWAC</title>
</head>
<body>
    <main>
        <h1>NPA VWAC</h1>
        <p>Welcome, {{ auth()->user()->name }}.</p>
        <p><a href="{{ route('profile.edit') }}">Manage your Jumuishi profile and password</a></p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </main>
</body>
</html>
