<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Access\UserRoleService;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;

class EnsureFirstAdmin extends Command
{
    protected $signature = 'bespoke:ensure-first-admin';

    protected $description = 'Garantiza que Bespoke-OS tenga al menos un administrador activo.';

    public function handle(UserRoleService $roles, AuditLogger $audit): int
    {
        if (User::query()->active()->where('role', User::ROLE_ADMIN)->exists()) {
            $this->info('Bespoke-OS ya tiene un administrador activo.');

            return self::SUCCESS;
        }

        $user = User::query()->active()->oldest('id')->first();

        if (! $user) {
            $this->error('No hay usuarios activos para asignar como administrador.');

            return self::FAILURE;
        }

        $roles->assertRoleChangeAllowed($user, User::ROLE_ADMIN);
        $previousRole = $user->role;
        $user->update(['role' => User::ROLE_ADMIN]);
        $audit->record('user.role_changed', $user, null, [
            'from' => $previousRole,
            'to' => User::ROLE_ADMIN,
            'source' => 'bespoke:ensure-first-admin',
        ]);

        $this->info("Administrador inicial asignado a {$user->email}.");

        return self::SUCCESS;
    }
}
