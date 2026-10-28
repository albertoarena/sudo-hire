<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="bg-white text-neutral-900 antialiased dark:bg-neutral-900 dark:text-neutral-100">
        <div id="app"></div>
    </body>
</html>
