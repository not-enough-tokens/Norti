<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta</title>
</head>
<body>

<h1>Crear cuenta</h1>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('register.store') }}">
    @csrf

    <label for="name">Nombre</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>

    <label for="email">Correo</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required>

    <label for="password">Contraseña</label>
    <input id="password" type="password" name="password" required>

    <label for="password_confirmation">Confirmar contraseña</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required>

    <button type="submit">Crear cuenta</button>
</form>

<p><a href="{{ route('login') }}">Ya tengo una cuenta</a></p>

</body>
</html>
