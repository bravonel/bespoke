<?php

namespace App\Services\Access;

use App\Models\User;
use DomainException;
use InvalidArgumentException;

class UserRoleService
{
    public function assertRoleChangeAllowed(User $user, ?string $newRole): void
    {
        if ($newRole !== null && ! array_key_exists($newRole, User::roleOptions())) {
            throw new InvalidArgumentException('Rol de usuario inválido.');
        }

        if ($user->isAdmin() && $newRole !== User::ROLE_ADMIN && ! $this->hasAnotherActiveAdmin($user)) {
            throw new DomainException('No puedes cambiar el rol del último administrador activo.');
        }
    }

    public function assertDeactivationAllowed(User $user): void
    {
        if ($user->isAdmin() && ! $this->hasAnotherActiveAdmin($user)) {
            throw new DomainException('No puedes dar de baja al último administrador activo.');
        }
    }

    private function hasAnotherActiveAdmin(User $user): bool
    {
        return User::query()
            ->active()
            ->where('role', User::ROLE_ADMIN)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
