<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-950">Confirma tu contraseña</h1>
        <p class="mt-1 text-sm text-slate-500">Esta sección requiere confirmación de identidad para continuar.</p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <div>
            <label class="field-label" for="password">Contraseña</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                name="password"
                class="field"
                required
                autofocus
                autocomplete="current-password"
            >
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="button-primary w-full justify-center py-3 text-base">
            Confirmar
        </button>
    </form>
</div>
