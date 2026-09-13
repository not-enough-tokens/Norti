<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `users.email` ya era `unique`, pero ese índice es case-sensitive: en
     * Postgres (y en la columna tal cual) "Ana@x.com" y "ana@x.com" pasaban
     * como dos correos distintos -- dos cuentas para el mismo correo, y solo
     * una de las dos combinaciones de mayúsculas podía iniciar sesión. Los
     * controllers ahora normalizan a minúsculas antes de guardar/comparar;
     * este índice es la barrera a nivel de BD para quien no pase por ahí.
     */
    public function up(): void
    {
        Schema::table('users', function (): void {
            DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (LOWER(email))');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (): void {
            DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');
        });
    }
};
