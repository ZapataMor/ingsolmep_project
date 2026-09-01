<x-layouts::auth.ingsolmep :title="__('Acceso')">
    <form
        method="POST"
        action="{{ route('login.store') }}"
        class="ing-card"
        novalidate
        x-data="{ shown: false, loading: false }"
        @submit="loading ? $event.preventDefault() : loading = true"
    >
        @csrf

        <svg class="ing-msignal" viewBox="0 0 220 40" aria-hidden="true">
            <path
                d="M 0 26 H 58 l 7 8 l 8 -26 l 9 34 l 8 -16 H 118 c 12 0 14 -14 28 -14 c 14 0 16 14 28 14 H 220"
                fill="none"
                stroke="#29ABE2"
                stroke-width="2.4"
                stroke-linejoin="round"
                stroke-linecap="round"
            />
        </svg>

        <h1 class="ing-wordmark"><span class="ing-wordmark-mark">INGS</span><span>OLMEP</span></h1>
        <p class="ing-subtitle">Sistema de Gestión de Equipos Médicos</p>

        <div class="ing-rule"></div>

        @if (session('status'))
            <div class="ing-status" x-show="!loading">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="ing-alert" role="alert" x-show="!loading">{{ $errors->first() }}</div>
        @endif

        <div class="ing-fields">
            <div class="ing-group">
                <label class="ing-label" for="ing-user">Usuario</label>
                <input
                    class="ing-field"
                    id="ing-user"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    placeholder="nombre.apellido"
                    autofocus
                />
            </div>

            <div class="ing-group">
                <div class="ing-label-row">
                    <label class="ing-label" for="ing-pass">Contraseña</label>
                    <button
                        class="ing-toggle"
                        type="button"
                        aria-controls="ing-pass"
                        :aria-pressed="shown ? 'true' : 'false'"
                        aria-pressed="false"
                        @click="shown = ! shown"
                        x-text="shown ? 'Ocultar' : 'Mostrar'"
                    >Mostrar</button>
                </div>
                <input
                    class="ing-field"
                    id="ing-pass"
                    name="password"
                    type="password"
                    :type="shown ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
            </div>
        </div>

        <div class="ing-submit-wrap">
            <button
                class="ing-submit"
                type="submit"
                data-test="login-button"
                :disabled="loading"
                :aria-busy="loading ? 'true' : 'false'"
            >
                <span x-text="loading ? 'Verificando…' : 'Ingresar'">Ingresar</span>
                <span class="ing-bar" x-show="loading" x-cloak></span>
            </button>
        </div>

        @if (Route::has('password.request'))
            <div class="ing-forgot-row">
                <a class="ing-forgot" href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
            </div>
        @endif
    </form>
</x-layouts::auth.ingsolmep>
