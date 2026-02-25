@extends('layouts.app')

@section('title', 'Slots - Backoffice')
@section('page-title', 'Slots')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Gerenciar Slots</h2>
            <div class="text-muted small">CRUD via API v1 • cursor pagination • filtros: q, status</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnNew">Novo slot</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Busca</label>
                    <input type="text" class="form-control" id="q"
                        placeholder="Título / Provider / Provider Game ID">
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
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
                        <th>Slot</th>
                        <th>Provider</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 220px;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="5" class="text-muted p-4">Carregando…</td>
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

    {{-- Modal Create/Edit --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTitle">Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Título</label>
                            <input class="form-control" id="f_title" placeholder="Ex: Gates of Olympus">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="f_status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Provider</label>
                            <input class="form-control" id="f_provider" placeholder="Ex: pragmatic">
                            <div class="form-text">Obrigatório • max 100</div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Provider Game ID</label>
                            <input class="form-control" id="f_provider_game_id" placeholder="Ex: game_123">
                            <div class="form-text">Obrigatório • único por provider</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Cover URL</label>
                            <input class="form-control" id="f_cover_url" placeholder="https://...">
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
        <script>
            const tbody = document.getElementById('tbody');
            const info = document.getElementById('paginationInfo');

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            let nextCursor = null;
            let prevCursor = null;

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

            function render(rows) {
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(item => `
      <tr>
        <td class="text-muted">#${item.id}</td>
        <td>
          <div class="fw-semibold">${item.title || '—'}</div>
          <div class="text-muted small">${item.cover_url || ''}</div>
        </td>
        <td class="text-muted">
          <div>${item.provider || '—'}</div>
          <div class="small text-muted">${item.provider_game_id || ''}</div>
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
            }

            function getFilters() {
                const q = document.getElementById('q').value.trim();
                const status = document.getElementById('status').value.trim();
                return {
                    q,
                    status
                };
            }

            async function load(cursor = null) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Carregando…</td></tr>`;

                const params = new URLSearchParams();
                if (cursor) params.set('cursor', cursor);

                const {
                    q,
                    status
                } = getFilters();
                if (q) params.set('q', q);
                if (status) params.set('status', status);

                const res = await apiFetch('/api/v1/slots?' + params.toString());
                if (!res.ok) {
                    toast('Falha ao carregar slots (' + res.status + ')', 'danger');
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                    return;
                }

                const data = await res.json();

                const rows = data.data || [];
                nextCursor = data.next_cursor || null;
                prevCursor = data.prev_cursor || null;

                info.textContent =
                    `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;
                render(rows);
            }

            function fillForm(item) {
                document.getElementById('f_title').value = item?.title || '';
                document.getElementById('f_status').value = item?.status || 'active';
                document.getElementById('f_provider').value = item?.provider || '';
                document.getElementById('f_provider_game_id').value = item?.provider_game_id || '';
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
                // document.getElementById('f_tags').value = Array.isArray(item?.tags) ? item.tags.join(',') : '';
                document.getElementById('f_cover_url').value = item?.cover_url || '';
            }

            function buildPayload() {
                const positionRaw = 0;
                const position = positionRaw === '' ? null : Number(positionRaw);

                const fileInput = document.getElementById('cover_url');
                const hasFile = fileInput && fileInput.files && fileInput.files[0];

                const payload = {
                    title: document.getElementById('f_title').value.trim(),
                    status: document.getElementById('f_status').value,
                    provider: document.getElementById('f_provider').value.trim(),
                    provider_game_id: document.getElementById('f_provider_game_id').value.trim(),
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
                editTitle.textContent = 'Novo Slot';
                saveError.classList.add('d-none');
                saveError.textContent = '';

                fillForm({
                    title: '',
                    status: 'active',
                    provider: '',
                    provider_game_id: '',
                    cover_url: '',
                });

                modal.show();
            }

            async function openEdit(id) {
                editingId = String(id);
                editTitle.textContent = 'Editar Slot #' + id;
                saveError.classList.add('d-none');
                saveError.textContent = '';

                // Preferível buscar o item completo
                const res = await apiFetch('/api/v1/slots/' + id);
                if (!res.ok) {
                    toast('Falha ao buscar slot (' + res.status + ')', 'danger');
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
                        if (value !== null && value !== undefined) {
                            if (typeof value === 'object') {
                                formData.append(key, JSON.stringify(value));
                            } else {
                                formData.append(key, value);
                            }
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
                const url = isEdit ? ('/api/v1/slots/' + editingId) : '/api/v1/slots';
                
                // Determinar método HTTP
                if (isEdit) {
                    options.method = 'PUT';
                } else {
                    options.method = 'POST';
                }
                
                options.body = body;

                const res = await apiFetch(url, options);

                if (!res.ok) {
                    await showApiError(saveError, res, 'Erro ao salvar slot');
                    return;
                }

                toast(isEdit ? 'Slot atualizado.' : 'Slot criado.');
                modal.hide();
                await load(null);
            }

            async function destroy(id) {
                if (!confirm('Excluir slot #' + id + '?')) return;

                const res = await apiFetch('/api/v1/slots/' + id, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    await toastApiError(res, 'excluir slot');
                    return;
                }

                toast('Slot excluído.');
                await load(null);
            }

            // UI events
            document.getElementById('btnNew').addEventListener('click', openNew);
            document.getElementById('btnReload').addEventListener('click', () => load(null));
            document.getElementById('btnSearch').addEventListener('click', () => load(null));
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                document.getElementById('status').value = '';
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

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'edit') openEdit(id);
                if (action === 'delete') destroy(id);
            });

            // initial
            load(null);
        </script>
    @endpush
@endsection
