<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-950">Verifica tu correo</h1>
        <p class="mt-2 text-sm text-slate-500">
            Gracias por registrarte. Antes de continuar, revisa tu bandeja de entrada y haz clic en el enlace de verificación que te enviamos. Si no lo recibiste, con gusto te mandamos otro.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            Se envió un nuevo enlace de verificación a tu correo electrónico.
        </div>
    @endif

    <div class="space-y-4">
        <button wire:click="sendVerification" type="button" class="button-primary w-full justify-center py-3 text-base">
            Reenviar correo de verificación
        </button>

        <button wire:click="logout" type="button" class="button-secondary w-full justify-center py-2.5 text-sm">
            Cerrar sesión
        </button>
    </div>
</div>
