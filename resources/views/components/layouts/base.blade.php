@props(['title' => null])

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ? "{$title} · Norti" : 'Norti' }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body {{ $attributes->class('min-h-screen font-sans text-text-primary antialiased') }}>
        {{ $slot }}
    </body>
</html>
