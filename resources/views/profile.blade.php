<x-app-layout>
    <x-slot name="header">
        <p class="page-kicker">Cuenta</p>
        <h1 class="page-title mt-2">Mi perfil</h1>
        <p class="mt-2 text-sm text-slate-600">Administra tu información personal, contraseña y configuración de cuenta.</p>
    </x-slot>

    <div class="shell space-y-6 max-w-2xl">
        <div class="panel p-7">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="panel p-7">
            <livewire:profile.update-password-form />
        </div>

        <div class="panel p-7">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
