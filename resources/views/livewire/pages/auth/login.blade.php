<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-950">Bienvenido de vuelta</h1>
        <p class="mt-1 text-sm text-slate-500">Ingresa tus credenciales para continuar.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label class="field-label" for="email">Correo electrónico</label>
            <input
                wire:model="form.email"
                id="email"
                type="email"
                name="email"
                class="field"
                required
                autofocus
                autocomplete="username"
            >
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <label class="field-label" for="password">Contraseña</label>
            <input
                wire:model="form.password"
                id="password"
                type="password"
                name="password"
                class="field"
                required
                autocomplete="current-password"
            >
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                    class="h-4 w-4 rounded border-stone-300 cursor-pointer"
                    style="accent-color:var(--brand-amber)">
                Recordarme
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    wire:navigate
                    class="text-sm font-medium hover:underline"
                    style="color:var(--brand-amber)"
                >
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button type="submit" class="button-primary w-full justify-center py-3 text-base">
            Entrar
        </button>
    </form>
</div>
