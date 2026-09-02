<?php

namespace App\Services\Access;

use App\Models\User;
use DomainException;
use InvalidArgumentException;

class UserRoleService
{
    public function assertRoleChangeAllowed(User $user, ?string $newRole, ?string $newEmail = null): void
    {
        if ($newRole !== null && ! array_key_exists($newRole, User::roleOptions())) {
            throw new InvalidArgumentException('Rol de usuario inválido.');
        }

        if ($newRole === User::ROLE_ADMIN && ! User::isSuperAdminEmail($newEmail ?? $user->email)) {
            throw new DomainException('El acceso de superadministrador está reservado para Sony y Marco.');
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
            ->get()
            ->contains(fn (User $candidate): bool => $candidate->isAdmin());
    }
}
