<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Login - Backoffice')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <style>
        :root {
            --radiant-lime: #c6d42d;
            --night-olive: #20210e;
            --snow-white: #eff1f2;
            --ebony-deep: #070707;

            --bs-body-bg: var(--snow-white);
            --bs-body-color: var(--ebony-deep);
            --bs-primary: var(--radiant-lime);
            --bs-primary-rgb: 198, 212, 45;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .auth-wrapper {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 520px) minmax(0, 1fr);
        }

        .auth-card {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-card-inner {
            width: 100%;
            max-width: 420px;
        }

        .auth-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--radiant-lime);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--night-olive);
            font-weight: 700;
        }

        .auth-aside {
            background: radial-gradient(circle at top left, var(--radiant-lime) 0, #a3b51f 25%, var(--night-olive) 85%, #000 100%);
            color: #fff;
            padding: 2.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-aside-title {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .auth-aside-highlight {
            color: var(--radiant-lime);
        }

        .btn-primary {
            --bs-btn-color: var(--night-olive);
            --bs-btn-bg: var(--radiant-lime);
            --bs-btn-border-color: var(--radiant-lime);

            --bs-btn-hover-color: var(--night-olive);
            --bs-btn-hover-bg: #d8ea54;
            --bs-btn-hover-border-color: #d8ea54;

            --bs-btn-active-color: var(--night-olive);
            --bs-btn-active-bg: #b8c927;
            --bs-btn-active-border-color: #b8c927;

            --bs-btn-focus-shadow-rgb: 198, 212, 45;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .auth-wrapper {
                grid-template-columns: minmax(0, 1fr);
            }
            .auth-aside {
                display: none;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-card-inner">
            <div class="d-flex align-items-center gap-2 mb-4">
                <div class="auth-brand-logo">BO</div>
                <div>
                    <div class="fw-semibold">Backoffice</div>
                    <div class="small text-muted">Painel de Conteúdo</div>
                </div>
            </div>

            @yield('content')
        </div>
    </div>

    <aside class="auth-aside">
        <div>
            <div class="auth-aside-title mb-3">
                Conteúdo sob <span class="auth-aside-highlight">controle</span>.
            </div>
            <p class="mb-0 text-white-50">
                Gerencie banners, vitrines, menus e destaques pelo painel da
                <span class="auth-aside-highlight">Bet</span>
                <span class="auth-aside-highlight">Aki</span>.
            </p>
        </div>

        <div class="small text-white-50 mt-4">
            © 2025 - Todos os direitos reservados.
        </div>
    </aside>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

@stack('scripts')
</body>
</html>