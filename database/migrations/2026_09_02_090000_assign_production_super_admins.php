<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereIn('email', [
                'sony@bespokeadvertising.com.mx',
                'marco@bespokeadvertising.com.mx',
            ])
            ->update(['role' => User::ROLE_ADMIN]);
    }

    public function down(): void
    {
        // El acceso no se revierte automáticamente para evitar bloquear administradores.
    }
};
