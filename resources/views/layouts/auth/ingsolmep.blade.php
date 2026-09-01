<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => request()->cookie('appearance', 'dark') !== 'light'])>
    <head>
        @include('partials.head')

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400&display=swap" rel="stylesheet" />

        <style>
            /* ---------------------------------------------------------------
               Pantalla de acceso INGSOLMEP
               Tokens: lima #8CC63F · verde oscuro #5A8F2B · carbón #3A3B3D
                       gris medio #58595B · azul señal #29ABE2 · neutro #F2F4F1
                       carbón profundo #23262A · borde #DCE0D8 · regla #E7EAE4
               --------------------------------------------------------------- */
            [x-cloak] {
                display: none !important;
            }

            .ing-body {
                margin: 0;
                padding: 0;
                background: #F2F4F1;
                font-family: 'IBM Plex Sans', system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .ing-root {
                position: relative;
                min-height: 100vh;
                background: #F2F4F1;
                overflow: hidden;
            }

            /* --- Zona gráfica derecha (capa de fondo) --- */
            .ing-graphic {
                position: absolute;
                inset: 0;
                clip-path: polygon(42% 0%, 100% 0%, 100% 100%, 24% 100%);
                background-color: #23262A;
            }

            /* La retícula se desborda 80 px por lado para que la deriva no
               descubra ningún borde. El paso mayor de 80 px es múltiplo del
               menor de 16 px, así que al recorrer 80 px el ciclo cierra
               exactamente y el bucle es imperceptible. */
            .ing-grid {
                position: absolute;
                inset: -80px;
                background-image:
                    repeating-linear-gradient(to right, rgba(255, 255, 255, 0.028) 0 1px, transparent 1px 16px),
                    repeating-linear-gradient(to bottom, rgba(255, 255, 255, 0.028) 0 1px, transparent 1px 16px),
                    repeating-linear-gradient(to right, rgba(255, 255, 255, 0.075) 0 1px, transparent 1px 80px),
                    repeating-linear-gradient(to bottom, rgba(255, 255, 255, 0.075) 0 1px, transparent 1px 80px);
                animation: ing-grid-drift 14s linear infinite;
                will-change: transform;
            }

            @keyframes ing-grid-drift {
                from { transform: translate3d(0, 0, 0); }
                to   { transform: translate3d(-80px, 80px, 0); }
            }

            .ing-plane-a {
                position: absolute;
                inset: 0;
                clip-path: polygon(74% 0%, 100% 0%, 100% 100%, 56% 100%);
                background: rgba(255, 255, 255, 0.035);
            }

            .ing-plane-b {
                position: absolute;
                inset: 0;
                clip-path: polygon(78% 62%, 100% 62%, 100% 100%, 71.2% 100%);
                background: rgba(140, 198, 63, 0.085);
            }

            .ing-signal {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                animation: ing-float 6s ease-in-out infinite;
                will-change: transform;
            }

            /* Flote vertical del trazo: recorrido total de 64 px. */
            @keyframes ing-float {
                0%   { transform: translateY(-32px); }
                50%  { transform: translateY(32px); }
                100% { transform: translateY(-32px); }
            }

            .ing-sweep {
                stroke-dasharray: 150 850;
                animation: ing-sweep 14s linear infinite;
            }

            @keyframes ing-sweep {
                from { stroke-dashoffset: 1000; }
                to { stroke-dashoffset: 0; }
            }

            /* --- Escenario del formulario --- */
            .ing-stage {
                position: relative;
                min-height: 100vh;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                padding: 64px 0 64px 8%;
            }

            /* --- Tarjeta de login --- */
            .ing-card {
                position: relative;
                z-index: 1;
                width: 460px;
                box-sizing: border-box;
                background: #FFFFFF;
                border: 1px solid #DCE0D8;
                border-top-color: #8CC63F;
                border-left-color: #8CC63F;
                border-radius: 16px;
                box-shadow:
                    0 1px 2px rgba(35, 38, 42, 0.05),
                    0 10px 24px -10px rgba(35, 38, 42, 0.28),
                    0 34px 60px -28px rgba(35, 38, 42, 0.45);
                padding: 46px 46px 42px;
                display: flex;
                flex-direction: column;
                gap: 0;
            }

            .ing-msignal {
                display: none;
                width: 132px;
                height: 34px;
                margin-bottom: 18px;
            }

            .ing-wordmark {
                margin: 0;
                text-align: center;
                font-size: 34px;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 1.05;
                color: #3A3B3D;
            }

            .ing-wordmark-mark {
                color: #8CC63F;
            }

            /* El sufijo societario acompaña al wordmark sin competir con él. */
            .ing-wordmark-suffix {
                margin-left: 9px;
                font-size: 17px;
                font-weight: 500;
                letter-spacing: 0;
                color: #58595B;
            }

            .ing-subtitle {
                margin: 10px 0 0;
                text-align: center;
                font-size: 14px;
                line-height: 1.45;
                color: #58595B;
            }

            .ing-rule {
                height: 1px;
                background: #E7EAE4;
                margin: 30px 0 28px;
            }

            /* --- Banners --- */
            .ing-alert {
                margin: 0 0 22px;
                padding: 12px 14px;
                background: #FAF0EE;
                border: 1px solid #E3C8C3;
                border-radius: 10px;
                font-size: 13px;
                line-height: 1.5;
                color: #8A2F26;
                text-wrap: pretty;
            }

            .ing-status {
                margin: 0 0 22px;
                padding: 12px 14px;
                background: #F2F4F1;
                border: 1px solid #DCE0D8;
                border-radius: 10px;
                font-size: 13px;
                line-height: 1.5;
                color: #3A3B3D;
                text-wrap: pretty;
            }

            /* --- Campos --- */
            .ing-fields {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .ing-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .ing-label-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                min-height: 20px;
            }

            .ing-label {
                font-size: 13.5px;
                font-weight: 500;
                color: #3A3B3D;
            }

            .ing-field {
                width: 100%;
                box-sizing: border-box;
                height: 48px;
                padding: 0 14px;
                background: #F2F4F1;
                border: 1px solid #DCE0D8;
                border-bottom: 2px solid #DCE0D8;
                border-radius: 10px;
                box-shadow: inset 0 1px 2px rgba(35, 38, 42, 0.05);
                font-family: 'IBM Plex Sans', sans-serif;
                font-size: 15px;
                font-weight: 400;
                color: #3A3B3D;
                outline: none;
                transition: border-color .12s ease, background-color .12s ease, box-shadow .12s ease;
            }

            .ing-field::placeholder {
                color: #9DA29A;
            }

            .ing-field:focus,
            .ing-field:focus-visible {
                border-color: #8CC63F;
                background: #FFFFFF;
                box-shadow: inset 0 1px 2px rgba(35, 38, 42, 0.03);
            }

            /* --- Botón Mostrar / Ocultar --- */
            .ing-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                margin: -4px -4px -4px 0;
                background: none;
                border: 0;
                border-radius: 8px;
                padding: 0;
                color: #58595B;
                cursor: pointer;
                transition: color .12s ease, background-color .12s ease;
            }

            .ing-toggle-icon {
                width: 18px;
                height: 18px;
            }

            .ing-toggle:hover {
                color: #3A3B3D;
                background: #F2F4F1;
            }

            .ing-toggle:focus-visible {
                outline: 2px solid #8CC63F;
                outline-offset: 3px;
            }

            /* --- Botón Ingresar --- */
            .ing-submit-wrap {
                margin-top: 26px;
            }

            .ing-submit {
                position: relative;
                overflow: hidden;
                width: 100%;
                height: 52px;
                border: 0;
                border-radius: 10px;
                background: linear-gradient(180deg, #46484A 0%, #3A3B3D 55%, #303133 100%);
                color: #FFFFFF;
                font-family: 'IBM Plex Sans', sans-serif;
                font-size: 15px;
                font-weight: 600;
                letter-spacing: 0.012em;
                cursor: pointer;
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.16),
                    inset 0 -1px 0 rgba(0, 0, 0, 0.35),
                    0 4px 10px -2px rgba(35, 38, 42, 0.42),
                    0 8px 20px -8px rgba(35, 38, 42, 0.5);
                transition: background-color .12s ease, color .12s ease, box-shadow .12s ease, transform .12s ease;
            }

            .ing-submit:hover {
                background: linear-gradient(180deg, #9BD24B 0%, #8CC63F 55%, #7CB432 100%);
                /* Texto en carbón: el blanco sobre lima queda en 1.9:1 y es
                   ilegible; el carbón sobre lima da 8.2:1. */
                color: #23262A;
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.28),
                    inset 0 -1px 0 rgba(0, 0, 0, 0.18),
                    0 6px 14px -2px rgba(90, 143, 43, 0.4),
                    0 12px 26px -10px rgba(90, 143, 43, 0.5);
                transform: translateY(-1px);
            }

            .ing-submit:active {
                background: linear-gradient(180deg, #5A8F2B 0%, #55872A 100%);
                color: #FFFFFF;
                box-shadow:
                    inset 0 2px 4px rgba(0, 0, 0, 0.35),
                    0 1px 2px rgba(35, 38, 42, 0.3);
                transform: translateY(1px);
            }

            .ing-submit:focus-visible {
                outline: 2px solid #8CC63F;
                outline-offset: 3px;
            }

            .ing-submit[disabled],
            .ing-submit[disabled]:hover,
            .ing-submit[disabled]:active {
                background: #58595B;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
                transform: none;
                cursor: default;
            }

            .ing-bar {
                position: absolute;
                left: 0;
                bottom: 0;
                height: 2px;
                width: 34%;
                background: #8CC63F;
                animation: ing-bar 1.15s linear infinite;
            }

            @keyframes ing-bar {
                0% { transform: translateX(-110%); }
                100% { transform: translateX(300%); }
            }

            /* --- Enlace de recuperación --- */
            .ing-forgot-row {
                margin-top: 18px;
                display: flex;
                justify-content: flex-end;
            }

            .ing-forgot {
                font-size: 12.5px;
                font-weight: 400;
                color: #58595B;
                text-decoration: none;
                border-bottom: 1px solid #DCE0D8;
                padding-bottom: 1px;
                transition: color .12s ease, border-color .12s ease;
            }

            .ing-forgot:hover {
                color: #3A3B3D;
                border-bottom-color: #8CC63F;
            }

            .ing-forgot:focus-visible {
                outline: 2px solid #8CC63F;
                outline-offset: 3px;
            }

            /* --- Pie de pantalla --- */
            .ing-footer {
                position: absolute;
                left: 8%;
                bottom: 30px;
                font-family: 'IBM Plex Mono', monospace;
                font-size: 11.5px;
                font-weight: 400;
                letter-spacing: 0.01em;
                color: #58595B;
            }

            /* --- Movimiento reducido --- */
            @media (prefers-reduced-motion: reduce) {
                .ing-sweep {
                    animation: none !important;
                    opacity: 0 !important;
                }

                .ing-signal,
                .ing-grid {
                    animation: none !important;
                    transform: none !important;
                }

                .ing-bar {
                    animation: none !important;
                    transform: none !important;
                    width: 100% !important;
                    opacity: .45;
                }
            }

            /* --- Móvil (<= 880 px) --- */
            @media (max-width: 880px) {
                .ing-graphic {
                    display: none;
                }

                .ing-stage {
                    padding: 52px 24px 120px;
                    align-items: flex-start;
                }

                .ing-card {
                    width: 100%;
                    max-width: 440px;
                    border: 0;
                    border-radius: 0;
                    box-shadow: none;
                    background: transparent;
                    padding: 0;
                }

                .ing-msignal {
                    display: block;
                    margin: 0 auto 18px;
                }

                .ing-field {
                    height: 52px;
                }

                .ing-toggle {
                    width: 44px;
                    height: 44px;
                    margin: -12px -12px -12px 0;
                }

                .ing-toggle-icon {
                    width: 20px;
                    height: 20px;
                }

                .ing-submit {
                    height: 54px;
                }

                .ing-footer {
                    left: 24px;
                }
            }
        </style>
    </head>
    <body class="ing-body">
        <div class="ing-root">
            <div class="ing-graphic" aria-hidden="true">
                <div class="ing-grid"></div>
                <div class="ing-plane-a"></div>
                <div class="ing-plane-b"></div>

                <svg class="ing-signal" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
                    <line x1="866" y1="96" x2="866" y2="812" stroke="#8CC63F" stroke-opacity="0.42" stroke-width="1.2" />
                    <path
                        id="ing-trace"
                        pathLength="1000"
                        d="M 60 470 H 300 c 14 0 18 -22 30 -22 c 12 0 16 22 30 22 H 404 l 10 12 l 12 -168 l 14 210 l 12 -54 H 500 c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 740 c 14 0 18 -22 30 -22 c 12 0 16 22 30 22 H 844 l 10 12 l 12 -168 l 14 210 l 12 -54 H 940 c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 1180 c 14 0 18 -22 30 -22 c 12 0 16 22 30 22 H 1284 l 10 12 l 12 -168 l 14 210 l 12 -54 H 1380 c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 1560"
                        fill="none"
                        stroke="#29ABE2"
                        stroke-opacity="0.5"
                        stroke-width="2.4"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                    />
                    <use class="ing-sweep" href="#ing-trace" stroke="#29ABE2" stroke-opacity="1" stroke-width="2.6" />
                    <circle cx="866" cy="314" r="5.5" fill="#8CC63F" />
                </svg>
            </div>

            <div class="ing-stage">
                {{ $slot }}

                <div class="ing-footer">INGSOLMEP S.A.S. &middot; Riohacha, La Guajira</div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
