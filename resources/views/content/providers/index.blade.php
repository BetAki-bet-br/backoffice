@extends('layouts.app')

@section('title', 'Provedores - Backoffice')
@section('page-title', 'Provedores')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Gerenciar Provedores</h2>
            <div class="text-muted small">Gerencie os fornecedores de jogos do casino</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" id="btnSync" title="Escanear jogos para encontrar provedores">Sincronizar
                (Scan)</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Busca (nome)</label>
                    <input type="text" class="form-control" id="q" placeholder="Ex: Pragmatic, Evolution...">
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Vertical (Filtro por Jogos)</label>
                    <select class="form-select" id="vertical">
                        <option value="">Todas</option>
                        <option value="slots">Slots</option>
                        <option value="live">Live Casino</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="">Todos</option>
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end mt-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" id="btnClear">Limpar</button>
                        <button class="btn btn-secondary" id="btnSearch">Buscar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-soft">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 120px;">Ext ID</th>
                        <th>Nome</th>
                        <th style="width: 120px;">Jogos</th>
                        <th style="width: 120px;">Verticals</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 180px;" class="text-end">Ações</th>
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

    {{-- Modal Edit Provider --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTitle">Editar Provedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nome</label>
                            <input class="form-control" id="f_name">
                        </div>

                        <div class="col-12">
                            <label class="form-label">External ID</label>
                            <input class="form-control" id="f_external_id" disabled readonly>
                            <div class="form-text">ID vindo da integração, não editável.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="f_status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Vertical(s)</label>
                            <div class="d-flex gap-3 pt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="f_verticals" value="slots"
                                        id="f_v_slots">
                                    <label class="form-check-label" for="f_v_slots">Slots (Casino)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="f_verticals" value="live"
                                        id="f_v_live">
                                    <label class="form-check-label" for="f_v_live">Live Casino</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none mt-3 small" id="saveError"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="btnSave">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal View Games --}}
    <div class="modal fade" id="gamesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gamesTitle">Jogos do Provedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 small">
                            <thead>
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Título</th>
                                    <th>Game ID (Ext)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="gamesTbody">
                                <tr>
                                    <td colspan="4" class="p-3 text-muted">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            const tbody = document.getElementById('tbody');

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            const gamesModal = new bootstrap.Modal(document.getElementById('gamesModal'));
            const gamesTbody = document.getElementById('gamesTbody');
            const gamesTitle = document.getElementById('gamesTitle');

            let editingId = null;

            function badge(status) {
                const s = (status || '').toLowerCase();
                const map = {
                    active: 'bg-success-subtle text-success',
                    inactive: 'bg-secondary-subtle text-secondary',
                };
                const cls = map[s] || 'bg-light text-muted';
                return `<span class="badge badge-status ${cls}">${status || '—'}</span>`;
            }

            function verticalBadge(vertical) {

                const v = (vertical || '').toLowerCase();

                const map = {

                    slots: 'bg-info-subtle text-info',

                    live: 'bg-primary-subtle text-primary',

                };

                const cls = map[v] || 'bg-light text-muted';

                return `<span class="badge ${cls}">${vertical || '—'}</span>`;

            }



            let sortableInstance = null;

            function initSortable() {

                if (sortableInstance) {

                    sortableInstance.destroy();

                }

                sortableInstance = new Sortable(tbody, {

                    handle: '.handle',

                    animation: 150,

                    ghostClass: 'bg-light',

                    onEnd: function(evt) {

                        const newOrder = Array.from(evt.from.children).map((row, index) => {

                            row.setAttribute('data-position', index);

                            return {

                                id: row.getAttribute('data-id'),

                                position: index

                            };

                        });

                        saveOrder(newOrder);

                    }

                });

            }



            async function saveOrder(newOrder) {

                const res = await apiFetch('/api/v1/providers/reorder', {

                    method: 'PUT',

                    body: JSON.stringify({
                        providers: newOrder
                    })

                });



                if (!res.ok) {

                    await toastApiError(res, 'reordenar provedores');

                    load(); // Reload to revert to original order if save failed

                } else {

                    toast('Ordem atualizada.');

                }

            }



            function render(rows) {

                if (!rows.length) {

                    tbody.innerHTML = `<tr><td colspan="8" class="text-muted p-4">Nenhum registro encontrado.</td></tr>`;

                    return;

                }



                // Client-side filtering for 'q' if needed, but API usually handles it.

                // ProviderController currently doesn't implement 'q' (search by name), only status/vertical.

                // I should probably update controller to support 'q' or filter client side.

                // Since the list of providers is not huge (usually < 200), client side is fine for now if API doesn't support.

                // But wait, the previous code had 'q' input.

                // Let's implement client-side filtering for 'q' since the controller doesn't seem to have it in the code I wrote.



                const q = document.getElementById('q').value.trim().toLowerCase();

                let filtered = rows;



                if (q) {

                    filtered = rows.filter(r =>

                        (r.name || '').toLowerCase().includes(q) ||

                        String(r.external_id || '').includes(q)

                    );

                }



                if (!filtered.length) {

                    tbody.innerHTML =
                        `<tr><td colspan="8" class="text-muted p-4">Nenhum registro encontrado (filtro local).</td></tr>`;

                    return;

                }



                tbody.innerHTML = filtered.map(item => `

        <tr data-id="${item.id}" data-position="${item.position ?? 0}">

          <td class="text-center align-middle handle" style="cursor: grab; width: 40px; color: #aaa;">

             <span class="fs-5">≡</span>

          </td>

          <td class="text-muted">#${item.id}</td>

          <td><code>${item.external_id}</code></td>

          <td class="fw-semibold">${item.name || '—'}</td>

          <td>${item.game_count ?? 0}</td>

          <td>

            ${(item.verticals || []).map(verticalBadge).join(' ')}

          </td>

          <td>${badge(item.status)}</td>

          <td class="text-end">

            <div class="d-flex justify-content-end gap-2">

              <button class="btn btn-sm btn-outline-primary" onclick="openGames(${item.id})">Jogos</button>

              <button class="btn btn-sm btn-outline-secondary" onclick="openEdit(${item.id})">Editar</button>

            </div>

          </td>

        </tr>

      `).join('');



                initSortable();

            }

            async function load() {
                tbody.innerHTML = `<tr><td colspan="8" class="text-muted p-4">Carregando…</td></tr>`;

                const params = new URLSearchParams();

                const status = document.getElementById('status').value;
                if (status) params.set('status', status);

                const vertical = document.getElementById('vertical').value;
                if (vertical) params.set('vertical', vertical);

                // Note: 'q' search is done client-side in render() for now as controller update wasn't requested for 'q'.

                const res = await apiFetch('/api/v1/providers?' + params.toString());
                if (!res.ok) {
                    toast('Falha ao carregar provedores', 'danger');
                    tbody.innerHTML = `<tr><td colspan="8" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                    return;
                }

                const data = await res.json();
                // API returns array directly (no pagination wrapper in the controller I saw)
                const rows = Array.isArray(data) ? data : (data.data || []);
                // Sort by position for initial render
                rows.sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
                render(rows);
            }

            // CRUD
            async function openEdit(id) {
                editingId = String(id);
                editTitle.textContent = 'Editar Provedor #' + id;
                saveError.classList.add('d-none');

                // We can fetch details or find in the list if we cache it. Fetching is safer.
                const res = await apiFetch('/api/v1/providers/' + id);
                if (!res.ok) return toast('Erro ao buscar detalhes', 'danger');

                const item = await res.json();

                document.getElementById('f_name').value = item.name;
                document.getElementById('f_external_id').value = item.external_id;
                document.getElementById('f_status').value = item.status;

                // Populate verticals checkboxes
                const providerVerticals = item.verticals || [];
                document.querySelectorAll('input[name="f_verticals"]').forEach(cb => {
                    cb.checked = providerVerticals.includes(cb.value);
                });

                editModal.show();
            }

            async function openGames(id) {
                gamesTbody.innerHTML = '<tr><td colspan="4" class="p-3 text-muted">Carregando...</td></tr>';
                gamesModal.show();

                const res = await apiFetch('/api/v1/providers/' + id); // This endpoint now includes games
                if (!res.ok) {
                    gamesTbody.innerHTML = '<tr><td colspan="4" class="p-3 text-danger">Erro ao carregar jogos.</td></tr>';
                    return;
                }

                const data = await res.json();
                gamesTitle.textContent = `Jogos de ${data.name}`;

                const games = data.games || [];
                if (!games.length) {
                    gamesTbody.innerHTML = '<tr><td colspan="4" class="p-3 text-muted">Nenhum jogo encontrado.</td></tr>';
                    return;
                }

                gamesTbody.innerHTML = games.map(g => `
        <tr>
            <td class="ps-3">${g.id}</td>
            <td class="fw-bold">${g.name}</td>
            <td><code>${g.externalId}</code></td>
            <td>${badge(!g.realPlayRestricted ? 'active' : 'inactive')}</td>
        </tr>
      `).join('');
            }

            async function save() {
                if (!editingId) return;

                // Get selected verticals
                const selectedVerticals = Array.from(document.querySelectorAll('input[name="f_verticals"]:checked')).map(
                    cb => cb.value);

                const payload = {
                    name: document.getElementById('f_name').value,
                    status: document.getElementById('f_status').value,
                    verticals: selectedVerticals
                };

                // Providers generally don't support creating via UI, only editing.
                const res = await apiFetch('/api/v1/providers/' + editingId, {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    await showApiError(saveError, res, 'Erro ao salvar provedor');
                    return;
                }

                toast('Provedor atualizado');
                editModal.hide();
                load();
            }

            async function sync() {
                if (!confirm('Isso irá escanear a tabela de jogos e atualizar a lista de provedores. Continuar?')) return;

                const btn = document.getElementById('btnSync');
                btn.disabled = true;
                btn.textContent = 'Sincronizando...';

                try {

                    const res = await apiFetch('/api/v1/providers/sync', {
                        method: 'POST'
                    });

                    if (res.ok) {

                        toast(`Sincronização iniciada em segundo plano.`);

                        load();

                    } else {

                        await toastApiError(res, 'sincronizar provedores');

                    }

                } catch (e) {

                    toast('Erro de conexão', 'danger');

                } finally {

                    btn.disabled = false;

                    btn.textContent = 'Sincronizar (Scan)';

                }

            }

            // Events
            document.getElementById('btnReload').addEventListener('click', load);
            document.getElementById('btnSearch').addEventListener('click', load);
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                document.getElementById('status').value = '';
                document.getElementById('vertical').value = '';
                load();
            });

            document.getElementById('btnSave').addEventListener('click', save);
            document.getElementById('btnSync').addEventListener('click', sync);

            // Init
            load();

            // Expose to window for inline onclick
            window.openEdit = openEdit;
            window.openGames = openGames;
        </script>
    @endpush
@endsection
