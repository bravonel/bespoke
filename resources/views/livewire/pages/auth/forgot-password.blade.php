<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-950">Recuperar contraseña</h1>
        <p class="mt-1 text-sm text-slate-500">Te enviamos un enlace para que puedas elegir una nueva.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <div>
            <label class="field-label" for="email">Correo electrónico</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                class="field"
                required
                autofocus
                autocomplete="username"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="button-primary w-full justify-center py-3 text-base">
            Enviar enlace de recuperación
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium hover:underline" style="color:var(--brand-amber)">
                Volver al inicio de sesión
            </a>
        </div>
    </form>
</div>
