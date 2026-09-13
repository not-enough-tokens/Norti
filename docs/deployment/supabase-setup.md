# Conectar el proyecto a Supabase (Postgres)

## Crear el proyecto

Al crear el proyecto en Supabase (free tier):

- **Enable Data API**: desactivar. Esta app nunca usa la API REST autogenerada de Supabase (PostgREST) -- Laravel se conecta directo por Postgres wire protocol vía Eloquent, que es el único punto de acceso a la base de datos (ver CLAUDE.md: "sin acceso directo a la base de datos"). Dejar la Data API prendida expondría las tablas por HTTP saltándose por completo los scopes de Passport, la lógica de las MCP tools y el audit log.
- **Automatically expose new tables**: desactivar (ya viene así por default).
- **Enable automatic RLS**: desactivar. Row Level Security está pensado para cuando el cliente pega directo a Supabase con el JWT de Supabase Auth (`auth.uid()` en las políticas) -- esta app no usa Supabase Auth, usa Passport/sesión de Laravel, así que RLS no aplica a su modelo de seguridad.
- **Postgres Type**: `Postgres` (default), no `OrioleDB` (todavía Alpha).
- **Region**: la más cercana a donde vivan los usuarios/el deploy.

## Connection string

En **Settings → Database → Connection string**, elegir **Session pooler**, no "Direct connection".

Free tier: la conexión directa (`db.<project-ref>.supabase.co:5432`) solo funciona por IPv6 a menos que pagues el add-on de IPv4. La mayoría de redes (oficina, casa, runners de GitHub Actions) son IPv4-only. El **Session pooler** sí soporta IPv4 y da una conexión persistente por sesión -- correcto para un proceso Laravel de larga duración (no serverless). El Transaction pooler (puerto 6543) también es una opción, pero está pensado para funciones serverless con conexiones cortas; el Session pooler es más parecido a una conexión directa normal.

Variables para `.env` (no compartir estos valores, cada quien pone los suyos):

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<tu contraseña de la DB>
DB_SSLMODE=require
```

Se usan variables sueltas en vez de pegar el `postgresql://...` completo en `DB_URL` para evitar tener que percent-encodear caracteres especiales de la contraseña dentro de una URL.

## Passport, por entorno

Cada entorno (local, staging, Supabase de producción) necesita su propio cliente personal-access -- vive en esa base de datos específica, no se comparte vía git ni entre entornos.

**No usar `php artisan passport:install` en este proyecto**: como las migrations de OAuth ya están publicadas y versionadas (`database/migrations/2026_09_12_1929*_create_oauth_*_table.php`), `passport:install` las vuelve a publicar con un timestamp nuevo y falla con `relation already exists` al intentar correrlas. Además, si ya existen `storage/oauth-*.key`, pide `--force` para no tocarlas.

En su lugar, por entorno nuevo:

```bash
php artisan migrate                                      # crea las tablas si no existen (usa las migrations ya versionadas)
php artisan passport:keys                                # solo si storage/oauth-*.key no existen todavía
php artisan passport:client --personal --name="..."      # crea el cliente personal-access que createToken() necesita
```

## Verificar la conexión

```bash
php artisan tinker
```
```php
DB::connection()->getPdo(); // debe regresar un objeto PDO sin lanzar excepción
```

Para confirmar que `jsonb` (usado en `audit_logs.input`) es un tipo real de Postgres y no el fallback de sqlite:
```php
DB::selectOne("select data_type from information_schema.columns where table_name = 'audit_logs' and column_name = 'input'")->data_type;
// debe regresar "jsonb"
```
