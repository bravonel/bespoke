<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-950">Eliminar cuenta</h2>
        <p class="mt-1 text-sm text-slate-500">Una vez eliminada, todos los datos se borrarán de forma permanente.</p>
    </div>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
    >
        Eliminar cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <div class="modal-header">
            <h2 class="text-lg font-semibold text-slate-950">¿Eliminar tu cuenta?</h2>
            <p class="mt-1 text-sm text-slate-500">
                Esta acción es permanente. Confirma con tu contraseña para continuar.
            </p>
        </div>

        <div class="modal-body">
            <form wire:submit="deleteUser" class="space-y-5">
                <div>
                    <label class="field-label sr-only" for="del-password">Contraseña</label>
                    <input
                        wire:model="password"
                        id="del-password"
                        name="password"
                        type="password"
                        class="field"
                        placeholder="Contraseña"
                        required
                    >
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                    <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                        Sí, eliminar cuenta
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</section>
