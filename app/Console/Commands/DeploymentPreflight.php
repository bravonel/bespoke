<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeploymentPreflight extends Command
{
    protected $signature = 'app:deployment-preflight {--backup-confirmed : Confirma que existe un respaldo reciente y verificable}';

    protected $description = 'Valida la aplicación y base activas antes de migrar producción';

    public function handle(): int
    {
        $database = (string) DB::connection()->getDatabaseName();
        $checks = [
            'Entorno production' => app()->environment('production'),
            '.env en la aplicación' => is_file(base_path('.env')),
            'APP_KEY configurada' => filled(config('app.key')),
            'Base no es fallback SQLite' => DB::getDriverName() !== 'sqlite',
            'Respaldo confirmado' => (bool) $this->option('backup-confirmed'),
        ];

        $this->table(['Dato', 'Valor'], [
            ['Ruta', base_path()],
            ['Entorno', app()->environment()],
            ['Driver', DB::getDriverName()],
            ['Base de datos', $database],
            ['APP_KEY fingerprint', substr(hash('sha256', (string) config('app.key')), 0, 12)],
        ]);

        foreach ($checks as $label => $passed) {
            $passed ? $this->info("OK: {$label}") : $this->error("FALLO: {$label}");
        }

        if (in_array(false, $checks, true)) {
            $this->error('Preflight fallido. No ejecutes migraciones en este contexto.');

            return self::FAILURE;
        }

        $this->info('Preflight aprobado. Ya puedes ejecutar php artisan migrate --force.');

        return self::SUCCESS;
    }
}
