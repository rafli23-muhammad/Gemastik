<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AegisExam')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        aegis: {
                            50: '#eef2ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="{{ asset('vendor/axios/axios.min.js') }}"></script>

    <style>
        html, body {
            margin: 0;
            min-height: 100%;
            background: #f4f7fb !important;
            color: #0f172a !important;
            font-family: Inter, system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full min-h-screen bg-[#f4f7fb] text-slate-900 font-sans antialiased">
    @yield('content')

    @stack('scripts')
    <script>
        (function () {
            const MAX_AUTO_RELOAD = 4;
            const reloadKey = 'aegis_blank_reload_' + location.pathname;

            function getReloadCount() {
                return Number(sessionStorage.getItem(reloadKey) || 0);
            }

            function bumpReloadCount() {
                sessionStorage.setItem(reloadKey, String(getReloadCount() + 1));
            }

            function resetReloadCount() {
                sessionStorage.removeItem(reloadKey);
            }

            function isExamTakePath() {
                return /\/exam\/?$/.test(location.pathname);
            }

            function isExamUiVisible() {
                const examInterface = document.getElementById('exam-interface');
                if (!examInterface) {
                    return false;
                }

                return !examInterface.classList.contains('hidden')
                    && examInterface.style.display !== 'none';
            }

            function shouldKeepFullscreen() {
                return window.aegisExamActive === true || isExamUiVisible();
            }

            function applyLightColors() {
                document.documentElement.style.backgroundColor = '#f4f7fb';
                document.body.style.backgroundColor = '#f4f7fb';
                document.body.style.color = '#0f172a';
            }

            function exitFullscreenIfAllowed() {
                if (shouldKeepFullscreen() || isExamTakePath()) {
                    return;
                }

                if (window.aegisDesktop && typeof window.aegisDesktop.setFullScreen === 'function') {
                    window.aegisDesktop.setFullScreen(false);
                }

                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(function () {});
                }
            }

            function isDarkBackground() {
                const bg = window.getComputedStyle(document.body).backgroundColor;
                return bg === 'rgb(0, 0, 0)'
                    || bg === 'rgb(3, 7, 18)'
                    || bg === 'rgb(9, 9, 11)'
                    || bg === 'rgb(24, 24, 27)';
            }

            function pageLooksBlank() {
                if (shouldKeepFullscreen() || isExamUiVisible()) {
                    return false;
                }

                const text = (document.body.innerText || '').replace(/\s+/g, ' ').trim();
                const hasMain = !!document.querySelector(
                    'form, h1, main, .card, .wrap, #server-connection-panel, #pre-exam-screen, #exam-interface, #blocked-fullscreen-overlay',
                );

                if (hasMain && text.length >= 8) {
                    return false;
                }

                return text.length < 12 || isDarkBackground();
            }

            function autoReloadIfBlank() {
                applyLightColors();
                exitFullscreenIfAllowed();

                if (!pageLooksBlank()) {
                    resetReloadCount();
                    return;
                }

                if (getReloadCount() >= MAX_AUTO_RELOAD) {
                    return;
                }

                bumpReloadCount();
                window.location.reload();
            }

            applyLightColors();
            document.addEventListener('DOMContentLoaded', function () {
                applyLightColors();
                exitFullscreenIfAllowed();
                window.setTimeout(autoReloadIfBlank, 900);
                window.setTimeout(autoReloadIfBlank, 2600);
            });
        })();
    </script>
</body>
</html>
