<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
</head>
<body>

<h1>¡Bienvenido, {{ $user->name }}!</h1>

<p>Aquí va a vivir el flujo de onboarding que detecta qué buscas (aprender, invertir, dar seguimiento a una meta,
    etc.). Todavía no está construido -- por ahora puedes continuar directo.</p>

<p>
    <a href="{{ route('education.index') }}">Continuar</a>
</p>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Cerrar sesión</button>
</form>

</body>
</html>
