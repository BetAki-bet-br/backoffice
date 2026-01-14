<script>
  const TOKEN_KEY = 'betaki_admin_token';

  function getToken() {
    return sessionStorage.getItem(TOKEN_KEY) || localStorage.getItem(TOKEN_KEY);
  }

  function clearToken() {
    sessionStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem('betaki_admin_expires_at');
    localStorage.removeItem('betaki_admin_expires_at');
  }

  async function apiFetch(url, options = {}) {
    const token = getToken();

    const headers = {
      'Accept': 'application/json',
      ...(options.headers || {}),
    };

    const hasBody = options.body !== undefined && options.body !== null;
    if (hasBody && !(options.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    if (token) headers['Authorization'] = 'Bearer ' + token;

    const res = await fetch(url, { ...options, headers });

    if (res.status === 401) {
      clearToken();
      window.location.href = '/login';
      return res;
    }

    return res;
  }

  function toast(message, type = 'success') {
    const id = 'toast_' + Math.random().toString(16).slice(2);
    const containerId = 'toastContainer';

    let container = document.getElementById(containerId);
    if (!container) {
      container = document.createElement('div');
      container.id = containerId;
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      container.style.zIndex = '2000';
      document.body.appendChild(container);
    }

    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + (type === 'danger' ? 'danger' : type) + ' border-0';
    el.id = id;
    el.role = 'alert';
    el.ariaLive = 'assertive';
    el.ariaAtomic = 'true';

    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    `;

    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3500 });
    t.show();

    el.addEventListener('hidden.bs.toast', () => el.remove());
  }
</script>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Backoffice')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap 5 via CDN --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    {{-- Estilos customizados --}}
    <style>
        :root {
            /* Paleta principal */
            --radiant-lime: #c6d42d;
            --night-olive: #20210e;
            --snow-white: #eff1f2;
            --ebony-deep: #070707;

            /* Override Bootstrap */
            --bs-body-bg: var(--snow-white);
            --bs-body-color: var(--ebony-deep);
            --bs-primary: var(--radiant-lime);
            --bs-primary-rgb: 198, 212, 45;
            --bs-secondary: var(--night-olive);
            --bs-secondary-rgb: 32, 33, 14;
            --bs-border-radius-lg: 1rem;
            --bs-border-radius: 0.75rem;
            --bs-link-color: var(--radiant-lime);
            --bs-link-hover-color: #d8ea54;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .layout-wrapper {
            min-height: 100vh;
            display: flex;
            background-color: var(--snow-white);
        }

        .sidebar {
            width: 260px;
            background-color: var(--night-olive);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            max-height: 100vh;
        }

        .sidebar-brand {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sidebar-brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--radiant-lime);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--night-olive);
            font-size: 1.1rem;
        }

        .sidebar-nav {
            padding: 1rem .75rem 1.5rem;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-section-title {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, 0.5);
            margin: .75rem 0 .25rem;
            padding: 0 .75rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .55rem .9rem;
            border-radius: .75rem;
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            font-size: .9rem;
            transition: background-color .15s ease, color .15s ease, transform .05s ease;
        }

        .sidebar-link:hover {
            background-color: rgba(198, 212, 45, 0.14);
            color: #fff;
        }

        .sidebar-link.active {
            background-color: var(--radiant-lime);
            color: var(--night-olive);
            font-weight: 600;
        }

        .sidebar-link-icon {
            width: 1.2rem;
            display: inline-flex;
            justify-content: center;
        }

        .sidebar-footer {
            padding: .75rem 1rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: .8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 60px;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .btn-sidebar-toggle {
            border-radius: 999px;
            padding: .25rem .55rem;
        }

        .content-main {
            flex: 1;
            padding: 1.5rem;
        }

        .card-soft {
            border-radius: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.04);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.03);
        }

        .table-soft {
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,.05);
        }

        .table-soft thead th {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(0,0,0,.55);
            background: rgba(0,0,0,.02);
            border-bottom: 1px solid rgba(0,0,0,.06);
        }

        .btn-primary {
            color: var(--night-olive) !important;
            font-weight: 600;
        }

        .badge-status {
            padding: .35rem .6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: .75rem;
        }

        /* Botões - padrão Betaki */
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

        .btn-outline-secondary {
            --bs-btn-color: var(--night-olive);
            --bs-btn-border-color: rgba(32, 33, 14, .35);
            --bs-btn-hover-bg: rgba(32, 33, 14, .08);
            --bs-btn-hover-border-color: rgba(32, 33, 14, .55);
            --bs-btn-hover-color: var(--night-olive);
        }

        @media (min-width: 1200px) {
            .modal-dialog.modal-xxl {
                --bs-modal-width: 1400px;
                max-width: var(--bs-modal-width);
            }
        }

        .modal-dialog.modal-xxl {
            width: calc(100vw - 2rem);
            max-width: calc(100vw - 2rem);
        }
        .modal .table-responsive { max-width: 100%; }

        /* Responsivo: colapsar sidebar no mobile */
        @media (max-width: 991.98px) {
            .layout-wrapper {
                flex-direction: column;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                transform: translateX(-100%);
                transition: transform .2s ease-out;
                z-index: 1040;
            }

            .sidebar.is-open {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="layout-wrapper">
    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">BO</div>
            <div>
                <div class="fw-semibold">Backoffice</div>
                <div class="small text-white-50">Content Manager</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🏠</span>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section-title">Conteúdo</div>


            <a href="{{ route('banners.ui') }}" class="sidebar-link {{ request()->routeIs('banners.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🖼️</span>
                <span>Banners</span>
            </a>

            <a href="{{ route('providers.ui') }}" class="sidebar-link {{ request()->routeIs('providers.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🏗️</span>
                <span>Provedores</span>
            </a>

            <a href="{{ route('slots.ui') }}" class="sidebar-link {{ request()->routeIs('slots.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🎰</span>
                <span>Slots</span>
            </a>

            <a href="{{ route('categories.ui') }}" class="sidebar-link {{ request()->routeIs('categories.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">📂</span>
                <span>Categorias</span>
            </a>

            <a href="{{ route('lobbies.ui') }}" class="sidebar-link {{ request()->routeIs('lobbies.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🏰</span>
                <span>Lobbies</span>
            </a>

            <a href="{{ route('menus.ui') }}" class="sidebar-link {{ request()->routeIs('menus.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🧭</span>
                <span>Menus</span>
            </a>

            <a href="{{ route('showcases.ui') }}" class="sidebar-link {{ request()->routeIs('showcases.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🧩</span>
                <span>Showcases</span>
            </a>

            <a href="{{ route('toplists.ui') }}" class="sidebar-link {{ request()->routeIs('toplists.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🏆</span>
                <span>Top 10 Lists</span>
            </a>

            <a href="{{ route('awards.ui') }}" class="sidebar-link {{ request()->routeIs('awards.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🥇</span>
                <span>Jogos premiados</span>
            </a>

            <a href="{{ route('topwinners.ui') }}" class="sidebar-link {{ request()->routeIs('topwinners.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">💰</span>
                <span>Vencedores</span>
            </a>

            <div class="sidebar-section-title mt-3">Configuração</div>

            <a href="{{ route('footers.ui') }}" class="sidebar-link {{ request()->routeIs('footers.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">📜</span>
                <span>Footer</span>
            </a>

            <a href="{{ route('users.ui') }}" class="sidebar-link {{ request()->routeIs('users.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">👤</span>
                <span>Usuários</span>
            </a>

            <a href="{{ route('gameextras.ui') }}" class="sidebar-link {{ request()->routeIs('gameextras.ui') ? 'active' : '' }}">
                <span class="sidebar-link-icon">🕹️</span>
                <span>Game Extras</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span>Logado como</span>
                <strong id="currentUserName">Admin</strong>
            </div>
        </div>
    </aside>

    {{-- Conteúdo principal --}}
    <div class="content-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn btn-outline-secondary d-lg-none btn-sidebar-toggle" type="button" id="sidebarToggle">
                    ☰
                </button>
                <h1 class="h5 mb-0">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted small d-none d-md-block">
                    Olá, <strong id="currentUserNameTop">Admin</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="logoutBtn">
                    Sair
                </button>
            </div>
        </header>

        <main class="content-main">
            @yield('content')
        </main>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('is-open');
            });
        }
    });
</script>

<script>
  async function apiLogout() {
    const token = getToken();
    if (!token) return;

    try {
      await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ' + token,
        }
      });
    } catch (_) {
      // ignore; we'll still clear local token
    }
  }

  async function apiMe() {
    const token = getToken();
    if (!token) return;

    try {
      const res = await apiFetch('/api/v1/auth/me');
      if (!res.ok) {
        clearToken();
        if (window.location.pathname !== '/login') {
          window.location.href = '/login';
        }
        return;
      }

      const me = await res.json();
      const name = me?.name || me?.email || 'Admin';

      const el1 = document.getElementById('currentUserName');
      if (el1) el1.textContent = name;

      const el2 = document.getElementById('currentUserNameTop');
      if (el2) el2.textContent = name;
    } catch (_) {
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const token = getToken();
    if (!token && window.location.pathname !== '/login') {
      window.location.href = '/login';
      return;
    }

    const btn = document.getElementById('logoutBtn');
    if (btn) {
      btn.addEventListener('click', async function () {
        await apiLogout();
        clearToken();
        window.location.href = '/login';
      });
    }

    apiMe();
  });
</script>


@stack('scripts')
</body>
</html>
