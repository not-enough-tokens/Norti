<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educación Financiera</title>
</head>
<body>

<h1>Educación Financiera</h1>

<p>Aprende conceptos importantes para tomar mejores decisiones financieras.</p>

<h2>Tu ruta de aprendizaje</h2>

@if($learningPath->isEmpty())
    <p>No hay temas disponibles.</p>
@else
    @foreach($learningPath as $topic)
        <div>
            <h3>{{ $topic->title }}</h3>

            <p>{{ $topic->description }}</p>

            <p>
                <strong>Dificultad:</strong>
                {{ $topic->difficulty }}
            </p>

            <p>
                <strong>Duración:</strong>
                {{ $topic->estimated_minutes }} minutos
            </p>

            <a href="{{ route('education.show', $topic) }}">
                Ver tema
            </a>
        </div>

        <hr>
    @endforeach
@endif

</body>
</html>
