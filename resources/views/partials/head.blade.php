<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Deja constancia en una cookie del aspecto que el navegador acaba de
     aplicar. El servidor pinta la clase `dark` del <html> a partir de ella,
     de modo que el atributo que `wire:navigate` copia de la respuesta ya
     coincide con el que está en pantalla: sin ese acuerdo, cada cambio de
     módulo forzaba el tema oscuro durante unos fotogramas. --}}
<script>
    (function () {
        var recordarAspecto = function () {
            var oscuro = document.documentElement.classList.contains('dark');

            document.cookie = 'appearance=' + (oscuro ? 'dark' : 'light') + ';path=/;max-age=31536000;samesite=lax';
        };

        var aplicarAspecto = window.Flux.applyAppearance;

        // Flux captura esta función al arrancar Alpine y la vuelve a llamar en
        // cada cambio de tema, así que envolverla mantiene la cookie al día.
        window.Flux.applyAppearance = function (aspecto) {
            aplicarAspecto(aspecto);
            recordarAspecto();
        };

        recordarAspecto();
    })();
</script>
