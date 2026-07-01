<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $area = '';
    public string $puesto = '';

    public function mount(): void
    {
        $this->name   = Auth::user()->name;
        $this->email  = Auth::user()->email;
        $this->area   = Auth::user()->area ?? '';
        $this->puesto = Auth::user()->puesto ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'area'   => ['nullable', 'string', 'max:255'],
            'puesto' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-950">Información del perfil</h2>
        <p class="mt-1 text-sm text-slate-500">Actualiza tu nombre y correo electrónico.</p>
    </div>

    <form wire:submit="updateProfileInformation" class="space-y-5">
        <div>
            <label class="field-label" for="name">Nombre</label>
            <input wire:model="name" id="name" name="name" type="text" class="field" required autofocus autocomplete="name">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="field-label" for="area">Área</label>
                <input wire:model="area" id="area" name="area" type="text" class="field" autocomplete="off">
                <x-input-error class="mt-2" :messages="$errors->get('area')" />
            </div>
            <div>
                <label class="field-label" for="puesto">Puesto</label>
                <input wire:model="puesto" id="puesto" name="puesto" type="text" class="field" autocomplete="off">
                <x-input-error class="mt-2" :messages="$errors->get('puesto')" />
            </div>
        </div>

        <div>
            <label class="field-label" for="email">Correo electrónico</label>
            <input wire:model="email" id="email" name="email" type="email" class="field" required autocomplete="username">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-slate-600">
                        Tu correo no está verificado.
                        <button wire:click.prevent="sendVerification" class="font-medium hover:underline" style="color:var(--brand-amber)">
                            Reenviar verificación
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-sm font-medium text-emerald-600">
                            Se envió un nuevo enlace de verificación.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-1">
            <button class="button-primary">Guardar</button>

            <x-action-message class="text-sm text-emerald-600" on="profile-updated">
                Guardado.
            </x-action-message>
        </div>
    </form>
</section>
