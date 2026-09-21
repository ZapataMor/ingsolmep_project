<?php

namespace App\Providers;

use App\Models\Mantenimiento;
use App\Models\Scopes\Visibilidad;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePermisos();
    }

    /**
     * Quién puede hacer qué.
     *
     * El scope global {@see Visibilidad} decide qué filas ve cada perfil; esto
     * decide cuáles puede tocar. Son dos preguntas distintas: el técnico ve su
     * orden y además la cierra, la institución ve la suya y no la toca.
     */
    protected function configurePermisos(): void
    {
        // El administrador es INGSOLMEP: nada del sistema le está vedado.
        Gate::before(fn (User $usuario): ?bool => $usuario->esAdministrador() ? true : null);

        Gate::define('gestionar-usuarios', fn (User $usuario): bool => false);
        Gate::define('gestionar-empresas', fn (User $usuario): bool => false);
        Gate::define('gestionar-equipos', fn (User $usuario): bool => false);

        // Asignar, reprogramar o borrar una orden es planeación, no ejecución.
        Gate::define('asignar-mantenimientos', fn (User $usuario): bool => false);

        // Ejecutar es cerrar la orden y dejar registrado lo que se hizo. Sólo
        // la puede cerrar quien la tiene asignada.
        Gate::define(
            'ejecutar-mantenimiento',
            fn (User $usuario, Mantenimiento $mantenimiento): bool => $usuario->esTecnico()
                && $mantenimiento->tecnico_id === $usuario->id,
        );

        // El documento se puede abrir siempre que se vea la orden; borrar la
        // constancia de que se emitió es otra cosa.
        Gate::define('gestionar-reportes', fn (User $usuario): bool => false);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
