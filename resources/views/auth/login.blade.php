@extends('layouts.auth')

@section('title', 'Login - Backoffice')

@section('content')
    <h1 class="h4 mb-3">Entrar</h1>
    <p class="text-muted mb-4">
        Use suas credenciais administrativas para acessar o painel.
    </p>

    <div id="loginAlert" class="alert alert-danger d-none small"></div>

    <form id="loginForm" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                required
                autofocus
            >
            <div class="invalid-feedback">
                Informe um e-mail válido.
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                required
            >
            <div class="invalid-feedback">
                Informe sua senha.
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label" for="remember">
                    Manter conectado
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">
            Acessar painel
        </button>
    </form>

    @push('scripts')
        <script>
            const TOKEN_KEY = 'betaki_admin_token';
            const EXPIRES_KEY = 'betaki_admin_expires_at';

            function showAlert(message) {
                const alert = document.getElementById('loginAlert');
                alert.textContent = message;
                alert.classList.remove('d-none');
            }

            function hideAlert() {
                const alert = document.getElementById('loginAlert');
                alert.classList.add('d-none');
                alert.textContent = '';
            }

            // Se já tiver token, manda pro dashboard
            (function () {
                const token = localStorage.getItem(TOKEN_KEY);
                if (token) window.location.href = "{{ route('dashboard') }}";
            })();

            document.getElementById('loginForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                e.stopPropagation();
                hideAlert();

                const form = e.currentTarget;
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                const btn = document.getElementById('loginBtn');
                btn.disabled = true;

                try {
                    const email = document.getElementById('email').value.trim();
                    const password = document.getElementById('password').value;
                    const remember = document.getElementById('remember').checked;

                    const res = await fetch('/api/v1/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ email, password })
                    });

                    if (!res.ok) {
                        // API retorna 401 com error.message
                        let msg = 'Credenciais inválidas.';
                        try {
                            const data = await res.json();
                            msg = data?.error?.message || msg;
                        } catch (_) {}
                        showAlert(msg);
                        return;
                    }

                    const data = await res.json();

                    // Salva token (padrão: localStorage)
                    localStorage.setItem(TOKEN_KEY, data.token);
                    localStorage.setItem(EXPIRES_KEY, data.expires_at || '');

                    // (Opcional) se NÃO marcou remember, usar sessionStorage ao invés de localStorage
                    if (!remember) {
                        sessionStorage.setItem(TOKEN_KEY, data.token);
                        sessionStorage.setItem(EXPIRES_KEY, data.expires_at || '');
                        localStorage.removeItem(TOKEN_KEY);
                        localStorage.removeItem(EXPIRES_KEY);
                    }

                    window.location.href = "{{ route('dashboard') }}";
                } catch (err) {
                    showAlert('Falha ao conectar. Tente novamente.');
                } finally {
                    btn.disabled = false;
                }
            });
        </script>
    @endpush
@endsection