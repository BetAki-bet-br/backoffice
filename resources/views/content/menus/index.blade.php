@extends('layouts.app')

@section('title', 'Menus - Backoffice')
@section('page-title', 'Menus')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Gerenciar Menus</h2>
            <div class="text-muted small">CRUD via API v1 - cursor pagination</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" id="btnSaveOrder" disabled>Salvar Ordem</button>
            <button class="btn btn-primary" id="btnNew">Novo menu</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Busca (nome/slug)</label>
                    <input type="text" class="form-control" id="q" placeholder="Ex: menu principal">
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="">Todos</option>
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3 d-flex gap-2">
                    <button class="btn btn-outline-secondary w-100" id="btnClear">Limpar</button>
                    <button class="btn btn-secondary w-100" id="btnSearch">Buscar</button>
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
                        <th style="width: 90px;">ID</th>
                        <th>Menu</th>
                        <th style="width: 120px;" class="text-center">Ordem</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 220px;" class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="6" class="text-muted p-4">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small" id="paginationInfo">-</div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="prevBtn">Anterior</button>
            <button class="btn btn-outline-secondary btn-sm" id="nextBtn">Proxima</button>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTitle">Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Nome</label>
                            <input class="form-control" id="f_name" placeholder="Ex: Menu principal">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Slug (opcional)</label>
                            <input class="form-control" id="f_slug" placeholder="Deixe vazio para gerar automaticamente">
                        </div>

                        <div class="col-6 col-lg-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="f_status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Meta (JSON)</label>
                            <textarea class="form-control font-monospace" rows="8" id="f_meta" spellcheck="false">{}</textarea>
                            <div class="form-text">Campo `meta` e um array no backend (cast).</div>
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

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            const tbody = document.getElementById('tbody');
            const info = document.getElementById('paginationInfo');
            const btnSaveOrder = document.getElementById('btnSaveOrder');

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            let nextCursor = null;
            let prevCursor = null;

            let editingId = null;
            let menuSortable = null;

            function render(rows) {
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    if (btnSaveOrder) btnSaveOrder.disabled = true;
                    return;
                }

                tbody.innerHTML = rows.map(item => `
      <tr data-menu-id="${item.id}" data-position="${item.position ?? 0}">
        <td class="text-center align-middle handle-menu" style="cursor: grab; width: 40px; color: #aaa;">
           <span class="fs-5">≡</span>
        </td>
        <td class="text-muted">#${item.id}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(item.name || '-')}</div>
          <div class="text-muted small">${escapeHtml(item.slug || '')}</div>
        </td>
        <td class="align-middle text-center">
           <div class="d-flex align-items-center justify-content-center gap-1">
             <button class="btn btn-sm btn-light border py-0 px-1" type="button" data-move-menu-up data-menu-id="${item.id}" title="Mover para cima">▲</button>
             <span class="badge bg-light text-dark border" style="min-width: 32px;" data-pos-display>${item.position ?? '-'}</span>
             <button class="btn btn-sm btn-light border py-0 px-1" type="button" data-move-menu-down data-menu-id="${item.id}" title="Mover para baixo">▼</button>
           </div>
        </td>
        <td>${badge(item.status)}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${item.id}">Editar</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');

                initMenuSortable();
                if (btnSaveOrder) btnSaveOrder.disabled = true;
            }

            function initMenuSortable() {
                if (menuSortable) return;
                menuSortable = new Sortable(tbody, {
                    handle: '.handle-menu',
                    animation: 150,
                    ghostClass: 'bg-light',
                    onEnd: function() {
                        if (btnSaveOrder) btnSaveOrder.disabled = false;
                        updateMenuPositionsVisual();
                    }
                });
            }

            function updateMenuPositionsVisual() {
                const rows = tbody.querySelectorAll('tr[data-menu-id]');
                const positions = Array.from(rows).map(r => Number(r.getAttribute('data-position')));
                const minPosition = positions.length > 0 ? Math.min(...positions) : 0;

                rows.forEach((row, i) => {
                    const newPos = minPosition + i;
                    row.querySelector('[data-pos-display]').textContent = newPos;
                    row.setAttribute('data-position', newPos);
                });
            }

            function moveMenu(id, direction) {
                const rows = Array.from(tbody.querySelectorAll('tr[data-menu-id]'));
                const idx = rows.findIndex(r => r.getAttribute('data-menu-id') === String(id));
                if (idx === -1) return;

                const newIdx = idx + direction;
                if (newIdx < 0 || newIdx >= rows.length) return;

                const row = rows[idx];
                const target = rows[newIdx];

                if (direction > 0) {
                    target.after(row);
                } else {
                    target.before(row);
                }

                if (btnSaveOrder) btnSaveOrder.disabled = false;
                updateMenuPositionsVisual();
            }

            async function saveOrder() {
                const rows = tbody.querySelectorAll('tr[data-menu-id]');
                const items = Array.from(rows).map(row => ({
                    id: Number(row.getAttribute('data-menu-id')),
                    position: Number(row.getAttribute('data-position'))
                }));

                const res = await apiFetch('/api/v1/menus/reorder', {
                    method: 'PUT',
                    body: JSON.stringify({
                        items
                    })
                });

                if (!res.ok) {
                    await toastApiError(res, 'salvar ordenação');
                    return;
                }

                toast('Ordem salva com sucesso.');
                if (btnSaveOrder) btnSaveOrder.disabled = true;
            }

            function getFilters() {
                return {
                    q: document.getElementById('q').value.trim(),
                    status: document.getElementById('status').value.trim(),
                };
            }

            async function load(cursor = null) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-muted p-4">Carregando...</td></tr>`;

                try {
                    const params = new URLSearchParams();
                    if (cursor) params.set('cursor', cursor);

                    const {
                        q,
                        status
                    } = getFilters();
                    if (q) params.set('q', q);
                    if (status) params.set('status', status);

                    const res = await apiFetch('/api/v1/menus?' + params.toString());
                    if (!res.ok) {
                        toast('Falha ao carregar menus (' + res.status + ')', 'danger');
                        tbody.innerHTML = `<tr><td colspan="6" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                        return;
                    }

                    const data = await res.json();
                    const rows = data.data || [];

                    nextCursor = data.next_cursor || null;
                    prevCursor = data.prev_cursor || null;

                    info.textContent =
                        `Carregados: ${rows.length} - Proximo: ${nextCursor ? 'sim' : 'nao'} - Anterior: ${prevCursor ? 'sim' : 'nao'}`;
                    render(rows);
                } catch (err) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger p-4">Erro inesperado ao carregar.</td></tr>`;
                    toast('Erro inesperado: ' + err.message, 'danger');
                }
            }

            function fillForm(item) {
                document.getElementById('f_name').value = item?.name || '';
                document.getElementById('f_slug').value = item?.slug || '';
                document.getElementById('f_status').value = item?.status || 'active';
                document.getElementById('f_meta').value = JSON.stringify(item?.meta || {}, null, 2);
            }

            function buildPayload() {
                const positionRaw = '0';
                const position = positionRaw === '' ? null : Number(positionRaw);

                let meta = {};
                try {
                    meta = JSON.parse(document.getElementById('f_meta').value || '{}');
                } catch {
                    meta = {};
                }

                const slug = document.getElementById('f_slug').value.trim();

                return {
                    name: document.getElementById('f_name').value.trim(),
                    slug: slug ? slug : null,
                    status: document.getElementById('f_status').value,
                    position,
                    meta,
                };
            }

            function openNew() {
                editingId = null;
                editTitle.textContent = 'Novo Menu';
                saveError.classList.add('d-none');
                saveError.textContent = '';

                fillForm({
                    name: '',
                    slug: '',
                    status: 'active',
                    meta: {}
                });
                modal.show();
            }

            async function openEdit(id) {
                editingId = String(id);
                editTitle.textContent = 'Editar Menu #' + id;
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const res = await apiFetch('/api/v1/menus/' + id);
                if (!res.ok) {
                    toast('Falha ao buscar menu (' + res.status + ')', 'danger');
                    return;
                }

                const item = await res.json();
                fillForm(item);
                modal.show();
            }

            async function save() {
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const payload = buildPayload();
                if (!payload.name) {
                    saveError.textContent = 'Nome e obrigatorio.';
                    saveError.classList.remove('d-none');
                    return;
                }
                if (!['active', 'inactive'].includes(payload.status)) {
                    saveError.textContent = 'Status invalido. Use active/inactive.';
                    saveError.classList.remove('d-none');
                    return;
                }

                try {
                    const isEdit = !!editingId;
                    const url = isEdit ? ('/api/v1/menus/' + editingId) : '/api/v1/menus';
                    const method = isEdit ? 'PUT' : 'POST';

                    const res = await apiFetch(url, {
                        method,
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        await showApiError(saveError, res, 'Erro ao salvar menu');
                        return;
                    }

                    toast(isEdit ? 'Menu atualizado.' : 'Menu criado.');
                    modal.hide();
                    await load(null);
                } catch (err) {
                    saveError.textContent = 'Erro inesperado: ' + err.message;
                    saveError.classList.remove('d-none');
                }
            }

            async function destroyMenu(id) {
                if (!confirm('Excluir menu #' + id + '?')) return;

                const res = await apiFetch('/api/v1/menus/' + id, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    await toastApiError(res, 'excluir menu');
                    return;
                }

                toast('Menu excluido.');
                await load(null);
            }

            document.getElementById('btnNew').addEventListener('click', openNew);
            document.getElementById('btnReload').addEventListener('click', () => load(null));
            document.getElementById('btnSearch').addEventListener('click', () => load(null));
            document.getElementById('q').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); load(); }
            });
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                document.getElementById('status').value = '';
                load(null);
            });

            document.getElementById('prevBtn').addEventListener('click', () => {
                if (!prevCursor) return toast('Sem pagina anterior.', 'secondary');
                load(prevCursor);
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                if (!nextCursor) return toast('Sem proxima pagina.', 'secondary');
                load(nextCursor);
            });

            document.getElementById('btnSave').addEventListener('click', save);

            if (btnSaveOrder) btnSaveOrder.addEventListener('click', saveOrder);

            tbody.addEventListener('click', (e) => {
                // Menu sorting arrows
                const btnUp = e.target.closest('button[data-move-menu-up]');
                if (btnUp) {
                    moveMenu(btnUp.getAttribute('data-menu-id'), -1);
                    return;
                }

                const btnDown = e.target.closest('button[data-move-menu-down]');
                if (btnDown) {
                    moveMenu(btnDown.getAttribute('data-menu-id'), 1);
                    return;
                }

                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'edit') openEdit(id);
                if (action === 'delete') destroyMenu(id);
            });

            load(null);
        </script>
    @endpush
@endsection
