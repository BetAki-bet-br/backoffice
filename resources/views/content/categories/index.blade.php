@extends('layouts.app')

@section('title', 'Categorias - Backoffice')
@section('page-title', 'Categorias')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Gerenciar Categorias</h2>
            <div class="text-muted small">CRUD via API v1 • cursor pagination • slots com posição (pivot)</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnNew">Nova categoria</button>
            <button class="btn btn-outline-dark" id="btnSync" title="Puxar categorias da API externa">Sincronizar
                Categorias</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Busca (nome/slug)</label>
                    <input type="text" class="form-control" id="q"
                        placeholder="Ex: cassino, slots, promocionais...">
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Vertical</label>
                    <select class="form-select" id="vertical">
                        <option value="">Todos</option>
                        <option value="slots">Slots (Casino)</option>
                        <option value="live">Live Casino</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="type">
                        <option value="">Todos</option>
                        <option value="game-list">Game List</option>
                        <option value="top-10-list">Top 10 List</option>
                        <option value="mais-premiados">Mais Premiados</option>
                        <option value="winners-list">Winners List</option>
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
                        <th style="width: 90px;">ID</th>
                        <th>Categoria</th>
                        <th style="width: 150px;">Vertical(s)</th>
                        <th style="width: 120px;">Tipo</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 120px;">Slots</th>
                        <th style="width: 320px;" class="text-end">Ações</th>
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
        <div class="text-muted small" id="paginationInfo">—</div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="prevBtn">Anterior</button>
            <button class="btn btn-outline-secondary btn-sm" id="nextBtn">Próxima</button>
        </div>
    </div>

    {{-- Modal Create/Edit Category --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTitle">Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Nome</label>
                            <input class="form-control" id="f_name" placeholder="Ex: Slots populares">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Slug (opcional)</label>
                            <input class="form-control" id="f_slug" placeholder="Deixe vazio para gerar automaticamente">
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label d-block">Vertical(s)</label>
                            <div class="d-flex gap-3 pt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="f_verticals" value="slots"
                                        id="v_slots">
                                    <label class="form-check-label" for="v_slots">Slots (Casino)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="f_verticals" value="live"
                                        id="v_live">
                                    <label class="form-check-label" for="v_live">Live Casino</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-4">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="f_type">
                                <option value="game-list">Game List</option>
                                <option value="top-10-list">Top 10 List</option>
                                <option value="mais-premiados">Mais Premiados</option>
                                <option value="winners-list">Winners List</option>
                            </select>
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
                            <div class="form-text">Campo `meta` é array no backend (cast).</div>
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

    {{-- Modal Sync Slots --}}
    <div class="modal fade" id="slotsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="slotsTitle">Slots da Categoria</h5>
                        <div class="text-muted small" id="slotsSubtitle">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-5">
                            <label class="form-label">Buscar slot (API /slots?q=)</label>
                            <div class="input-group">
                                <input class="form-control" id="slotSearch"
                                    placeholder="Ex: gates, pragmatic, game_id...">
                                <button class="btn btn-outline-secondary" type="button"
                                    id="btnSlotSearch">Buscar</button>
                            </div>

                            <div class="mt-3 table-soft">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Resultado</th>
                                                <th style="width: 90px;" class="text-end">Add</th>
                                            </tr>
                                        </thead>
                                        <tbody id="slotResults">
                                            <tr>
                                                <td colspan="2" class="text-muted p-3">Faça uma busca para adicionar
                                                    slots.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="text-muted small mt-2">
                                Dica: a API de slots também usa cursor, mas aqui estamos buscando só os primeiros resultados
                                para adicionar rápido.
                            </div>
                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Slots vinculados</label>
                                <div class="d-flex gap-2">
                                    {{-- Botão auxiliar para salvar apenas a ordem, se desejado, mas o Salvar Slots já faz isso --}}
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                        id="btnSavePositions">Salvar Posicionamentos</button>
                                </div>
                            </div>

                            <div class="mt-2 table-soft">
                                <div class="table-responsive">
                                    <table class="table mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;"></th>
                                                <th>Slot</th>
                                                <th style="width: 120px;" class="text-center">Ordem</th>
                                                <th style="width: 90px;" class="text-end">Remover</th>
                                            </tr>
                                        </thead>
                                        <tbody id="linkedSlots">
                                            <tr>
                                                <td colspan="4" class="text-muted p-4">Carregando…</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="alert alert-danger d-none mt-3 small" id="syncError"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button class="btn btn-primary" id="btnSyncSlots">Salvar slots</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            // ====== Listagem / Cursor pagination ======
            const tbody = document.getElementById('tbody');
            const info = document.getElementById('paginationInfo');

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            const slotsModal = new bootstrap.Modal(document.getElementById('slotsModal'));
            const slotsTitle = document.getElementById('slotsTitle');
            const slotsSubtitle = document.getElementById('slotsSubtitle');
            const linkedSlotsTbody = document.getElementById('linkedSlots');
            const slotResultsTbody = document.getElementById('slotResults');
            const syncError = document.getElementById('syncError');

            let nextCursor = null;
            let prevCursor = null;

            let editingId = null;
            let managingCategoryId = null;

            // cache da lista (para editar rápido)
            const cacheById = new Map();

            // slots vinculados (estado do modal)
            // Map slot_id -> { slot_id, position, slot: {id,title,provider,provider_game_id,status} }
            const linkedMap = new Map();

            function badge(status) {
                const s = (status || '').toLowerCase();
                const map = {
                    active: 'bg-success-subtle text-success',
                    inactive: 'bg-secondary-subtle text-secondary',
                    slots: 'bg-info-subtle text-info-emphasis',
                    live: 'bg-warning-subtle text-warning-emphasis'
                };
                const cls = map[s] || 'bg-light text-muted';
                return `<span class="badge badge-status ${cls}">${status || '—'}</span>`;
            }

            function render(rows) {
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(item => `
      <tr data-cat-id="${item.id}" data-position="${item.position ?? 0}">
        <td class="text-muted">#${item.id}</td>
        <td>
          <div class="fw-semibold">${item.name || '—'}</div>
          <div class="text-muted small">${item.slug || ''}</div>
        </td>
        <td>${(item.verticals || []).map(v => badge(v)).join(' ')}</td>
        <td><code class="small">${item.type || 'game-list'}</code></td>
        <td>${badge(item.status)}</td>
        <td class="text-muted">${Array.isArray(item.slots) ? item.slots.length : (item.slots_count ?? '—')}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${item.id}">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-action="slots" data-id="${item.id}">Slots</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');
            }

            function getFilters() {
                return {
                    q: document.getElementById('q').value.trim(),
                    status: document.getElementById('status').value.trim(),
                    vertical: document.getElementById('vertical').value.trim(),
                    type: document.getElementById('type').value.trim(),
                };
            }

            async function load(cursor = null) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-muted p-4">Carregando…</td></tr>`;

                const params = new URLSearchParams();
                if (cursor) params.set('cursor', cursor);

                const {
                    q,
                    status,
                    vertical,
                    type
                } = getFilters();
                if (q) params.set('q', q);
                if (status) params.set('status', status);
                if (vertical) params.set('vertical', vertical);
                if (type) params.set('type', type);

                const res = await apiFetch('/api/v1/categories?' + params.toString());
                if (!res.ok) {
                    toast('Falha ao carregar categorias (' + res.status + ')', 'danger');
                    tbody.innerHTML = `<tr><td colspan="9" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                    return;
                }

                const data = await res.json();
                const rows = data.data || [];

                cacheById.clear();
                rows.forEach(r => cacheById.set(String(r.id), r));

                nextCursor = data.next_cursor || null;
                prevCursor = data.prev_cursor || null;

                info.textContent =
                    `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;
                render(rows);
            }

            // ====== CRUD Category ======
            function fillForm(item) {
                document.getElementById('f_name').value = item?.name || '';
                document.getElementById('f_slug').value = item?.slug || '';

                const verticals = item?.verticals || (item?.vertical ? [item.vertical] : ['slots']);
                document.querySelectorAll('input[name="f_verticals"]').forEach(cb => {
                    cb.checked = verticals.includes(cb.value);
                });

                document.getElementById('f_type').value = item?.type || 'game-list';
                document.getElementById('f_status').value = item?.status || 'active';


                // Atualizar hidden field de cover_url (se existir)
                const coverUrlField = document.getElementById('f_cover_url');
                if (coverUrlField) {
                    coverUrlField.value = item?.cover_url || '';
                }
                // Reset file input
                const fileInput = document.getElementById('cover_url');
                if (fileInput) {
                    fileInput.value = '';
                }

                document.getElementById('f_meta').value = JSON.stringify(item?.meta || {}, null, 2);
            }

            function buildPayload() {
                const positionRaw = 0;
                const position = positionRaw === '' ? null : Number(positionRaw);

                let meta = {};
                try {
                    meta = JSON.parse(document.getElementById('f_meta').value || '{}');
                } catch {
                    meta = {};
                }

                const slug = document.getElementById('f_slug').value.trim();
                const verticals = Array.from(document.querySelectorAll('input[name="f_verticals"]:checked')).map(cb => cb
                    .value);

                const fileInput = document.getElementById('cover_url');
                const hasFile = fileInput && fileInput.files && fileInput.files[0];

                const payload = {
                    name: document.getElementById('f_name').value.trim(),
                    slug: slug ? slug : null, // se null, backend gera a partir do name
                    verticals: verticals,
                    type: document.getElementById('f_type').value,
                    status: document.getElementById('f_status').value,
                    position,
                    meta,
                };

                // Só incluir cover_url se há novo arquivo
                // Se não há arquivo novo, não enviamos o campo (para não validar como nulo)
                if (!hasFile) {
                    // Sem novo arquivo - manter a URL atual (do hidden field)
                    const coverUrlField = document.getElementById('f_cover_url');
                    const coverUrl = coverUrlField ? (coverUrlField.value.trim() || null) : null;
                    if (coverUrl) {
                        payload.cover_url = coverUrl;
                    }
                }

                return payload;
            }

            function openNew() {
                editingId = null;
                editTitle.textContent = 'Nova Categoria';
                saveError.classList.add('d-none');
                saveError.textContent = '';

                fillForm({
                    name: '',
                    slug: '',
                    vertical: 'slots',
                    type: 'game-list',
                    status: 'active',
                    position: 0,
                    meta: {}
                });
                editModal.show();
            }

            async function openEdit(id) {
                editingId = String(id);
                editTitle.textContent = 'Editar Categoria #' + id;
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const res = await apiFetch('/api/v1/categories/' + id);
                if (!res.ok) {
                    toast('Falha ao buscar categoria (' + res.status + ')', 'danger');
                    return;
                }

                const item = await res.json();
                fillForm(item);
                editModal.show();
            }

            async function save() {
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const payload = buildPayload();
                if (!payload.name) {
                    saveError.textContent = 'Nome é obrigatório.';
                    saveError.classList.remove('d-none');
                    return;
                }
                if (!['active', 'inactive'].includes(payload.status)) {
                    saveError.textContent = 'Status inválido. Use active/inactive.';
                    saveError.classList.remove('d-none');
                    return;
                }

                const fileInput = document.getElementById('cover_url');
                const hasFile = fileInput && fileInput.files && fileInput.files[0];

                let body;
                let options = { method: 'POST' };

                if (hasFile) {
                    // Usar FormData para enviar arquivo
                    const formData = new FormData();
                    formData.append('cover_url', fileInput.files[0]);
                    
                    // Adicionar TODOS os campos do payload
                    Object.keys(payload).forEach(key => {
                        const value = payload[key];
                        
                        if (value === null || value === undefined) {
                            return;
                        }
                        
                        if (Array.isArray(value)) {
                            // Arrays: verticals[0], verticals[1], etc
                            value.forEach((item, i) => {
                                if (typeof item === 'object') {
                                    formData.append(`${key}[${i}]`, JSON.stringify(item));
                                } else {
                                    formData.append(`${key}[${i}]`, item);
                                }
                            });
                        } else if (typeof value === 'object') {
                            // Objetos: serializar como JSON
                            formData.append(key, JSON.stringify(value));
                        } else {
                            // Valores simples
                            formData.append(key, value);
                        }
                    });
                    
                    body = formData;
                    
                    // Para PUT com FormData, usar method spoofing do Laravel
                    if (editingId) {
                        formData.append('_method', 'PUT');
                    }
                } else {
                    // Usar JSON para requisições sem arquivo
                    body = JSON.stringify(payload);
                    options.headers = { 'Content-Type': 'application/json' };
                }

                const isEdit = !!editingId;
                const url = isEdit ? ('/api/v1/categories/' + editingId) : '/api/v1/categories';
                
                // Determinar método HTTP
                if (isEdit) {
                    options.method = 'PUT';
                } else {
                    options.method = 'POST';
                }
                
                options.body = body;

                const res = await apiFetch(url, options);

                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    saveError.textContent = body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')');
                    saveError.classList.remove('d-none');
                    return;
                }

                toast(isEdit ? 'Categoria atualizada.' : 'Categoria criada.');
                editModal.hide();
                await load(null);
            }

            async function destroyCategory(id) {
                if (!confirm('Excluir categoria #' + id + '?')) return;

                const res = await apiFetch('/api/v1/categories/' + id, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    toast('Falha ao excluir (' + res.status + ')', 'danger');
                    return;
                }
                toast('Categoria excluída.');
                await load(null);
            }

            // ====== Slots modal (sync) ======
            let sortableInstance = null;

            function renderLinkedSlots() {
                // Ordena pelo position atual
                const items = Array.from(linkedMap.values()).sort((a, b) => (a.position ?? 0) - (b.position ?? 0));

                if (!items.length) {
                    linkedSlotsTbody.innerHTML = `<tr><td colspan="4" class="text-muted p-4">Nenhum slot vinculado.</td></tr>`;
                    return;
                }

                linkedSlotsTbody.innerHTML = items.map((it, idx) => `
      <tr data-slot-id="${it.slot_id}">
        <td class="text-center align-middle handle" style="cursor: grab; width: 40px; color: #aaa;">
           <span class="fs-5">≡</span>
        </td>
        <td class="align-middle">
          <div class="fw-semibold">${it.slot?.title || ('Slot #' + it.slot_id)}</div>
          <div class="text-muted small">${it.slot?.provider || ''} ${it.slot?.provider_game_id ? '• '+it.slot.provider_game_id : ''}</div>
        </td>
        <td class="align-middle text-center">
           <div class="d-flex align-items-center justify-content-center gap-1">
             <button class="btn btn-sm btn-light border py-0 px-1" type="button" data-move-up data-slot-id="${it.slot_id}" title="Mover para cima">▲</button>
             <span class="badge bg-light text-dark border" style="min-width: 32px;">${idx + 1}</span>
             <button class="btn btn-sm btn-light border py-0 px-1" type="button" data-move-down data-slot-id="${it.slot_id}" title="Mover para baixo">▼</button>
           </div>
        </td>
        <td class="text-end align-middle">
          <button class="btn btn-sm btn-outline-danger" data-remove-slot data-slot-id="${it.slot_id}">Remover</button>
        </td>
      </tr>
    `).join('');
            }

            function updateMapPositions() {
                const rows = linkedSlotsTbody.querySelectorAll('tr[data-slot-id]');
                rows.forEach((row, idx) => {
                    const id = row.getAttribute('data-slot-id');
                    if (linkedMap.has(id)) {
                        linkedMap.get(id).position = idx;
                    }
                });
            }

            function initSortable() {
                if (sortableInstance) return;
                sortableInstance = new Sortable(linkedSlotsTbody, {
                    handle: '.handle',
                    animation: 150,
                    ghostClass: 'bg-light',
                    onEnd: function() {
                        updateMapPositions();
                        renderLinkedSlots();
                    }
                });
            }

            function moveItem(id, direction) {
                const items = Array.from(linkedMap.values()).sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
                const idx = items.findIndex(it => String(it.slot_id) === String(id));
                if (idx === -1) return;

                const newIdx = idx + direction;
                if (newIdx < 0 || newIdx >= items.length) return;

                // Swap and re-index
                const moved = items.splice(idx, 1)[0];
                items.splice(newIdx, 0, moved);

                items.forEach((it, i) => {
                    linkedMap.get(String(it.slot_id)).position = i;
                });

                renderLinkedSlots();
            }

            async function openSlotsModal(categoryId) {
                managingCategoryId = String(categoryId);
                syncError.classList.add('d-none');
                syncError.textContent = '';
                linkedMap.clear();
                linkedSlotsTbody.innerHTML = `<tr><td colspan="4" class="text-muted p-4">Carregando…</td></tr>`;

                const res = await apiFetch('/api/v1/categories/' + categoryId);
                if (!res.ok) {
                    toast('Falha ao carregar slots da categoria (' + res.status + ')', 'danger');
                    return;
                }

                const category = await res.json();
                slotsTitle.textContent = 'Slots da Categoria';
                slotsSubtitle.innerHTML =
                    `${category.name} • ${category.slug || ''} • ${(category.verticals || []).map(v => badge(v)).join(' ')}`;

                (category.slots || []).forEach(s => {
                    linkedMap.set(String(s.id), {
                        slot_id: s.id,
                        position: s.pivot?.position ?? 0,
                        slot: {
                            id: s.id,
                            title: s.title,
                            provider: s.provider,
                            provider_game_id: s.provider_game_id,
                            status: s.status
                        }
                    });
                });

                renderLinkedSlots();
                initSortable();

                slotResultsTbody.innerHTML =
                    `<tr><td colspan="2" class="text-muted p-3">Faça uma busca para adicionar slots.</td></tr>`;
                document.getElementById('slotSearch').value = '';

                slotsModal.show();
            }

            async function searchSlots() {
                const q = document.getElementById('slotSearch').value.trim();
                if (!q) {
                    slotResultsTbody.innerHTML =
                        `<tr><td colspan="2" class="text-muted p-3">Digite algo para buscar.</td></tr>`;
                    return;
                }

                slotResultsTbody.innerHTML = `<tr><td colspan="2" class="text-muted p-3">Buscando…</td></tr>`;

                const params = new URLSearchParams({
                    q
                });
                const res = await apiFetch('/api/v1/slots?' + params.toString());
                if (!res.ok) {
                    slotResultsTbody.innerHTML =
                        `<tr><td colspan="2" class="text-danger p-3">Erro ao buscar slots (${res.status}).</td></tr>`;
                    return;
                }

                const data = await res.json();
                const rows = data.data || [];

                if (!rows.length) {
                    slotResultsTbody.innerHTML =
                        `<tr><td colspan="2" class="text-muted p-3">Nenhum slot encontrado.</td></tr>`;
                    return;
                }

                slotResultsTbody.innerHTML = rows.slice(0, 12).map(s => `
      <tr>
        <td>
          <div class="fw-semibold">${s.title}</div>
          <div class="text-muted small">${s.provider} • ${s.provider_game_id} • ${s.status}</div>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" data-add-slot data-slot-id="${s.id}">Adicionar</button>
        </td>
      </tr>
    `).join('');
            }

            function addSlotToLinked(slotId, slotObj = null) {
                const key = String(slotId);
                if (linkedMap.has(key)) {
                    toast('Esse slot já está vinculado.', 'secondary');
                    return;
                }

                // Append to end
                const items = Array.from(linkedMap.values());
                const maxPos = items.length > 0 ? Math.max(...items.map(i => i.position ?? 0)) : -1;

                linkedMap.set(key, {
                    slot_id: Number(slotId),
                    position: maxPos + 1,
                    slot: slotObj
                });
                renderLinkedSlots();
            }

            async function syncSlots() {
                syncError.classList.add('d-none');
                syncError.textContent = '';

                // Garante normalização 0..N antes de salvar
                const items = Array.from(linkedMap.values()).sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
                const payloadItems = items.map((it, idx) => ({
                    slot_id: it.slot_id,
                    position: idx,
                }));

                const res = await apiFetch(`/api/v1/categories/${managingCategoryId}/slots`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        items: payloadItems
                    })
                });

                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    syncError.textContent = body ? JSON.stringify(body) : ('Erro ao salvar slots (' + res.status + ')');
                    syncError.classList.remove('d-none');
                    return;
                }

                toast('Slots sincronizados com sucesso.');
                slotsModal.hide();
                await load(null);
            }

            // ====== Eventos ======
            document.getElementById('btnNew').addEventListener('click', openNew);
            document.getElementById('btnReload').addEventListener('click', () => load(null));
            document.getElementById('btnSearch').addEventListener('click', () => load(null));
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                document.getElementById('status').value = '';
                document.getElementById('vertical').value = '';
                document.getElementById('type').value = '';
                load(null);
            });

            document.getElementById('prevBtn').addEventListener('click', () => {
                if (!prevCursor) return toast('Sem página anterior.', 'secondary');
                load(prevCursor);
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                if (!nextCursor) return toast('Sem próxima página.', 'secondary');
                load(nextCursor);
            });

            document.getElementById('btnSave').addEventListener('click', save);

            async function syncCategories() {
                if (!confirm(
                        'Deseja sincronizar categorias da API externa? Isso pode criar novas categorias e atualizar nomes existentes.'
                    )) return;

                const portalId = prompt('Portal ID (Desktop=5, Mobile=6):', '5');
                if (!portalId) {
                    toast('Portal ID é obrigatório.', 'secondary');
                    return;
                }

                const levelId = prompt('Digite o Level ID (opcional):', '');

                const btn = document.getElementById('btnSync');
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Sincronizando...';

                try {
                    const payload = {
                        portal_id: parseInt(portalId, 10),
                    };

                    if (levelId) {
                        payload.level_id = parseInt(levelId, 10);
                    }

                    const res = await apiFetch('/api/v1/categories/sync', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        toast('Erro ao iniciar a sincronização.', 'danger');
                    } else {
                        toast(`Sincronização iniciada em segundo plano.`);
                        await load(null);
                    }
                } catch (e) {
                    console.error(e);
                    toast('Erro de conexão.', 'danger');
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }

            document.getElementById('btnSync').addEventListener('click', syncCategories);

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'edit') openEdit(id);
                if (action === 'slots') openSlotsModal(id);
                if (action === 'delete') destroyCategory(id);
            });

            document.getElementById('btnSlotSearch').addEventListener('click', searchSlots);
            document.getElementById('slotSearch').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchSlots();
                }
            });

            // adicionar slot a partir dos resultados
            slotResultsTbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-add-slot]');
                if (!btn) return;

                const slotId = btn.getAttribute('data-slot-id');
                const row = btn.closest('tr');

                const title = row.querySelector('.fw-semibold')?.textContent?.trim() || '';
                const meta = row.querySelector('.text-muted')?.textContent?.trim() || '';
                addSlotToLinked(slotId, {
                    id: Number(slotId),
                    title,
                    provider: meta
                });
            });

            // remover/editar posições nos vinculados
            linkedSlotsTbody.addEventListener('click', (e) => {
                const btnRemove = e.target.closest('button[data-remove-slot]');
                if (btnRemove) {
                    const slotId = btnRemove.getAttribute('data-slot-id');
                    linkedMap.delete(String(slotId));
                    renderLinkedSlots();
                    return;
                }

                const btnUp = e.target.closest('button[data-move-up]');
                if (btnUp) {
                    moveItem(btnUp.getAttribute('data-slot-id'), -1);
                    return;
                }

                const btnDown = e.target.closest('button[data-move-down]');
                if (btnDown) {
                    moveItem(btnDown.getAttribute('data-slot-id'), 1);
                    return;
                }
            });

            document.getElementById('btnSavePositions').addEventListener('click', syncSlots);
            document.getElementById('btnSyncSlots').addEventListener('click', syncSlots);


            // init
            load(null);
        </script>
    @endpush
@endsection
