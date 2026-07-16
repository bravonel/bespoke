<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Access\UserRoleService;
use DomainException;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    protected $signature = 'bespoke:assign-role
        {email : Correo del usuario existente}
        {role=admin : Rol de negocio a asignar}';

    protected $description = 'Asigna de forma explícita un rol de negocio a un usuario de Bespoke-OS.';

    public function handle(UserRoleService $roles): int
    {
        $user = User::query()->where('email', mb_strtolower((string) $this->argument('email')))->first();
        $role = (string) $this->argument('role');

        if (! $user) {
            $this->error('No se encontró un usuario con ese correo.');

            return self::FAILURE;
        }

        try {
            $roles->assertRoleChangeAllowed($user, $role);
        } catch (DomainException|\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $user->update(['role' => $role]);
        $this->info("Rol {$role} asignado a {$user->email}.");

        return self::SUCCESS;
    }
}
