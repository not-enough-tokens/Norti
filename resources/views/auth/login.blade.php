<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
</head>
<body>

<h1>Iniciar sesión</h1>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('login.store') }}">
    @csrf

    <label for="email">Correo</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

    <label for="password">Contraseña</label>
    <input id="password" type="password" name="password" required>

    <label>
        <input type="checkbox" name="remember"> Recordarme
    </label>

    <button type="submit">Entrar</button>
</form>

<p><a href="{{ route('register') }}">Crear una cuenta</a></p>

</body>
</html>
