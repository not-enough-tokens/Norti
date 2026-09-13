<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $topic->title }}</title>
</head>
<body>

<a href="{{ route('education.index') }}">
    ← Volver a educación
</a>

<h1>{{ $topic->title }}</h1>

<p>{{ $topic->description }}</p>

<p>
    <strong>Categoría:</strong>
    {{ $topic->category }}
</p>

<p>
    <strong>Dificultad:</strong>
    {{ $topic->difficulty }}
</p>

<p>
    <strong>Tiempo estimado:</strong>
    {{ $topic->estimated_minutes }} minutos
</p>

<hr>

<div>
    {!! nl2br(e($topic->content)) !!}
</div>

</body>
</html>
