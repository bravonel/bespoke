<nav class="-mx-3 flex flex-1 justify-end">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="button-secondary"
        >
            Entrar
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="button-secondary"
        >
            Iniciar sesión
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="button-primary ms-3"
            >
                Crear acceso
            </a>
        @endif
    @endauth
</nav>
