@extends('layouts.app')

@section('title', 'Game Extras')
@section('page-title', 'Game Extras')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">RTP / Volatilidade / Aposta mínima</h2>
            <div class="text-muted small">Dados do CSV + match com jogos sincronizados da API base</div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-primary" id="btnSync">
                Sincronizar base
            </button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-8">
                    <label class="form-label">Buscar external_id</label>
                    <input type="text" class="form-control" id="q" placeholder="Ex: 12345">
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2">
                    <button class="btn btn-secondary w-100" id="btnSearch">Buscar</button>
                    <button class="btn btn-outline-secondary w-100" id="btnClear">Limpar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-soft">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>External ID</th>
                        <th>RTP</th>
                        <th>Volatilidade</th>
                        <th>Aposta mínima</th>
                        <th>Existe na base?</th>
                        <th>Nome (base)</th>
                        <th>Fornecedor</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="7" class="text-muted p-4">Carregando…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small" id="pageInfo">—</div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="prevBtn">Anterior</button>
            <button class="btn btn-outline-secondary btn-sm" id="nextBtn">Próxima</button>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

    @push('scripts')
        <script>
            function toast(message, variant = 'success') {
                const container = document.querySelector('.toast-container');
                const el = document.createElement('div');
                const cls = variant === 'danger' ? 'danger' : (variant === 'warning' ? 'warning' : (variant === 'secondary' ?
                    'secondary' : 'success'));
                el.className = 'toast align-items-center text-bg-' + cls + ' border-0';
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'assertive');
                el.setAttribute('aria-atomic', 'true');
                el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    `;
                container.appendChild(el);
                const t = new bootstrap.Toast(el, {
                    delay: 2600
                });
                t.show();
                el.addEventListener('hidden.bs.toast', () => el.remove());
            }

            const tbody = document.getElementById('tbody');
            const pageInfo = document.getElementById('pageInfo');

            const qInput = document.getElementById('q');

            let currentPage = 1;
            let lastPage = 1;

            function getParams(pageOverride = null) {
                const q = qInput.value.trim();
                const page = pageOverride ?? currentPage;

                const params = new URLSearchParams();
                params.set('portal_id', 5);
                if (q) params.set('q', q);
                params.set('page', page);
                return params;
            }

            function renderRows(rows) {
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(r => {
                    const exists = !!r.exists_in_base;
                    const badge = exists ?
                        `<span class="badge text-bg-success">Sim</span>` :
                        `<span class="badge text-bg-secondary">Não</span>`;

                    return `
        <tr>
          <td class="fw-semibold">${r.external_id ?? '—'}</td>
          <td>${r.rtp ?? '—'}</td>
          <td>${r.volatility ?? '—'}</td>
          <td>${r.min_bet ?? '—'}</td>
          <td>${badge}</td>
          <td class="text-muted">${r.base_name ?? '—'}</td>
          <td>
            <div class="small text-muted">${r.base_product_name ?? ''}</div>
          </td>
        </tr>
      `;
                }).join('');
            }

            async function load(page = 1) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>`;

                const params = getParams(page);
                const res = await apiFetch('/api/v1/game-extras/overview?' + params.toString());

                if (!res.ok) {
                    tbody.innerHTML =
                        `<tr><td colspan="8" class="text-danger p-4">Erro ao carregar (${res.status}).</td></tr>`;
                    await toastApiError(res, 'carregar game extras');
                    return;
                }

                const json = await res.json();

                // paginate padrão do Laravel: data, current_page, last_page, total...
                const rows = json.data || [];
                currentPage = json.current_page || 1;
                lastPage = json.last_page || 1;

                renderRows(rows);

                pageInfo.textContent = `Página ${currentPage} de ${lastPage} • Total: ${json.total ?? rows.length}`;
            }

            async function syncBase() {
                const btn = document.getElementById('btnSync');
                btn.disabled = true;
                btn.textContent = 'Sincronizando...';

                const res = await apiFetch('/api/v1/game-extras/sync', {
                    method: 'POST',
                });

                btn.disabled = false;
                btn.textContent = 'Sincronizar base';

                if (!res.ok) {
                    await toastApiError(res, 'sincronizar base');
                    return;
                }

                const data = await res.json();
                toast(data.message || 'Sincronização concluída.');

                await load(1);
            }

            document.getElementById('btnSearch').addEventListener('click', () => load(1));
            document.getElementById('btnClear').addEventListener('click', () => {
                qInput.value = '';
                load(1);
            });

            document.getElementById('prevBtn').addEventListener('click', () => {
                if (currentPage <= 1) return toast('Sem página anterior.', 'secondary');
                load(currentPage - 1);
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                if (currentPage >= lastPage) return toast('Sem próxima página.', 'secondary');
                load(currentPage + 1);
            });

            document.getElementById('btnSync').addEventListener('click', syncBase);

            document.addEventListener('DOMContentLoaded', () => {
                load(1);
            });
        </script>
    @endpush
@endsection
