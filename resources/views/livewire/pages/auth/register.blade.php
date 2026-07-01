<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-950">Crear cuenta</h1>
        <p class="mt-1 text-sm text-slate-500">Ingresa tus datos para unirte al equipo.</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <div>
            <label class="field-label" for="name">Nombre completo</label>
            <input wire:model="name" id="name" type="text" name="name" class="field" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label class="field-label" for="email">Correo electrónico</label>
            <input wire:model="email" id="email" type="email" name="email" class="field" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label class="field-label" for="password">Contraseña</label>
            <input wire:model="password" id="password" type="password" name="password" class="field" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label class="field-label" for="password_confirmation">Confirmar contraseña</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" class="field" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="button-primary w-full justify-center py-3 text-base">
            Crear cuenta
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium hover:underline" style="color:var(--brand-amber)">
                ¿Ya tienes cuenta? Inicia sesión
            </a>
        </div>
    </form>
</div>
