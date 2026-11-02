<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sudo Hire Settings</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="placeholder">
        {{-- M8c replaces this page with the eight settings panes. --}}
        <main>
            <h1>Settings</h1>
            <p>The settings panes are not built yet. Until then, change the settings with <code>php artisan tinker</code>.</p>
        </main>
    </body>
</html>
