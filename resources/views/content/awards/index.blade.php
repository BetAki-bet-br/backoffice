@extends('layouts.app')

@section('title', 'Jogos premiados - Backoffice')
@section('page-title', 'Jogos premiados')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Lotes de Jogos Premiados</h2>
            <div class="text-muted small">CRUD via API v1 • cursor pagination • sync results • publish/archive</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnNew">Novo lote</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label">Busca (título)</label>
                    <input type="text" class="form-control" id="q" placeholder="Ex: Top ganhos semana 50">
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label">Vertical</label>
                    <select class="form-select" id="vertical">
                        <option value="">Todas</option>
                        <option value="slots">slots</option>
                        <option value="live">live</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="">Todos</option>
                        <option value="draft">draft</option>
                        <option value="review">review</option>
                        <option value="published">published</option>
                        <option value="archived">archived</option>
                    </select>
                </div>

                <div class="col-12 col-lg-3 d-flex gap-2">
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
                        <th>Lote</th>
                        <th style="width: 120px;">Vertical</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 220px;">Período</th>
                        <th style="width: 420px;" class="text-end">Ações</th>
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

    {{-- Modal Create/Edit Batch --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTitle">Lote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Título</label>
                            <input class="form-control" id="f_title" placeholder="Ex: Top ganhos da semana">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Vertical</label>
                            <select class="form-select" id="f_vertical">
                                <option value="slots">slots</option>
                                <option value="live">live</option>
                            </select>
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="f_status">
                                <option value="draft">draft</option>
                                <option value="review">review</option>
                                <option value="published">published</option>
                                <option value="archived">archived</option>
                            </select>
                            <div class="form-text">Publish exige status=review + resultados.</div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Período início</label>
                            <input type="date" class="form-control" id="f_period_start">
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Período fim</label>
                            <input type="date" class="form-control" id="f_period_end">
                        </div>


                        <div class="col-12">
                            <label class="form-label">Criteria (JSON)</label>
                            <textarea class="form-control font-monospace" rows="10" id="f_criteria" spellcheck="false">{}</textarea>
                            <div class="form-text">
                                Aceita: metric, min_win, countries[], provider, tags[].
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info small mb-0">
                                <strong>Resultados:</strong> use o botão <em>Resultados</em> na tabela depois de salvar.
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

    {{-- Modal Sync Results --}}
    <div class="modal fade" id="resultsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Resultados do lote</h5>
                        <div class="text-muted small" id="resultsSubtitle">—</div>
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
                                                <td colspan="3" class="text-muted p-3">Faça uma busca para adicionar.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Itens do lote</label>
                            </div>

                            <div class="mt-2 table-soft">
                                <div class="table-responsive">
                                    <table class="table mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Slot</th>
                                                <th style="width: 140px;">Prêmio Inicial</th>
                                                <th style="width: 140px;">Prêmio Final</th>
                                                <th style="width: 110px;">Intervalo (min)</th>
                                                <th style="width: 90px;" class="text-end">Remover</th>
                                            </tr>
                                        </thead>
                                        <tbody id="linkedResults">
                                            <tr>
                                                <td colspan="8" class="text-muted p-4">Carregando…</td>
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
                    <button class="btn btn-primary" id="btnSyncResults">Salvar resultados</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const tbody = document.getElementById('tbody');
            const info = document.getElementById('paginationInfo');

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));
            const resultsSubtitle = document.getElementById('resultsSubtitle');
            const slotResultsTbody = document.getElementById('slotResults');
            const linkedResultsTbody = document.getElementById('linkedResults');
            const syncError = document.getElementById('syncError');

            let nextCursor = null;
            let prevCursor = null;

            let editingId = null;
            let managingBatchId = null;

            const linkedMap = new Map(); // slot_id -> item

            function badge(statusOrVertical) {
                const s = (statusOrVertical || '').toLowerCase();
                const map = {
                    published: 'bg-success-subtle text-success',
                    review: 'bg-info-subtle text-info',
                    draft: 'bg-warning-subtle text-warning',
                    archived: 'bg-secondary-subtle text-secondary',
                    slots: 'bg-info-subtle text-info-emphasis', // Added for verticals
                    live: 'bg-warning-subtle text-warning-emphasis', // Added for verticals
                };
                const cls = map[s] || 'bg-light text-muted';
                return `<span class="badge badge-status ${cls}">${statusOrVertical || '—'}</span>`;
            }

            function fmtDate(dt) {
                if (!dt) return '—';
                try {
                    // Retorna apenas a parte da data no formato local
                    return new Date(dt).toLocaleDateString('pt-BR', {
                        timeZone: 'UTC'
                    });
                } catch {
                    return dt;
                }
            }

            function getFilters() {
                return {
                    q: document.getElementById('q').value.trim(),
                    vertical: document.getElementById('vertical').value.trim(),
                    status: document.getElementById('status').value.trim(),
                };
            }

            async function load(cursor = null) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>`;

                const params = new URLSearchParams();
                if (cursor) params.set('cursor', cursor);

                const {
                    q,
                    vertical,
                    status
                } = getFilters();
                if (q) params.set('q', q);
                if (vertical) params.set('vertical', vertical);
                if (status) params.set('status', status);

                const res = await apiFetch('/api/v1/awards/batches?' + params.toString());
                if (!res.ok) {
                    toast('Falha ao carregar lotes (' + res.status + ')', 'danger');
                    tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                    return;
                }

                const data = await res.json();
                const rows = data.data || [];

                nextCursor = data.next_cursor || null;
                prevCursor = data.prev_cursor || null;

                info.textContent =
                    `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;

                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(b => `
      <tr>
        <td class="text-muted">#${b.id}</td>
        <td>
          <div class="fw-semibold">${b.title || '—'}</div>
        </td>
        <td>${badge(b.vertical)}</td>
        <td>${badge(b.status)}</td>
        <td class="text-muted small">${fmtDate(b.period_start)} → ${fmtDate(b.period_end)}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${b.id}">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-action="results" data-id="${b.id}">Resultados</button>
            <button class="btn btn-sm btn-outline-success" data-action="publish" data-id="${b.id}">Publicar</button>
            <button class="btn btn-sm btn-outline-warning" data-action="archive" data-id="${b.id}">Arquivar</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${b.id}">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');
            }

            function isoToInputDate(dt) {
                if (!dt) return '';
                // Extrai apenas a parte da data (YYYY-MM-DD) de uma string ISO
                return dt.substring(0, 10);
            }

            function fillForm(batch) {
                document.getElementById('f_title').value = batch?.title || '';
                document.getElementById('f_vertical').value = batch?.vertical || 'slots';
                document.getElementById('f_status').value = batch?.status || 'draft';
                document.getElementById('f_period_start').value = isoToInputDate(batch?.period_start);
                document.getElementById('f_period_end').value = isoToInputDate(batch?.period_end);
                document.getElementById('f_criteria').value = JSON.stringify(batch?.criteria || {}, null, 2);
            }

            function buildPayload() {
                let criteria = {};
                try {
                    criteria = JSON.parse(document.getElementById('f_criteria').value || '{}');
                } catch {
                    criteria = {};
                }

                return {
                    title: document.getElementById('f_title').value.trim(),
                    vertical: document.getElementById('f_vertical').value,
                    status: document.getElementById('f_status').value,
                    period_start: document.getElementById('f_period_start').value,
                    top_n: 10,
                    period_end: document.getElementById('f_period_end').value,
                    criteria,
                };
            }

            function openNew() {
                editingId = null;
                editTitle.textContent = 'Novo lote';
                saveError.classList.add('d-none');
                saveError.textContent = '';
                fillForm({
                    title: '',
                    vertical: 'slots',
                    status: 'draft',
                    period_start: '',
                    period_end: '',
                    criteria: {}
                });
                editModal.show();
            }

            async function openEdit(id) {
                editingId = String(id);
                editTitle.textContent = 'Editar lote #' + id;
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const res = await apiFetch('/api/v1/awards/batches/' + id);
                if (!res.ok) return toast('Falha ao buscar lote (' + res.status + ')', 'danger');

                const batch = await res.json();
                fillForm(batch);
                editModal.show();
            }

            async function saveBatch() {
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const payload = buildPayload();

                // validações básicas do request
                const allowedVertical = ['slots', 'live'];
                const allowedStatus = ['draft', 'review', 'published', 'archived'];
                if (!payload.title) return showSaveError('Título é obrigatório.');
                if (!allowedVertical.includes(payload.vertical)) return showSaveError('Vertical inválida.');
                if (!allowedStatus.includes(payload.status)) return showSaveError('Status inválido.');
                if (!payload.period_start || !payload.period_end) return showSaveError(
                    'Período início e fim são obrigatórios.');

                const isEdit = !!editingId;
                const url = isEdit ? ('/api/v1/awards/batches/' + editingId) : '/api/v1/awards/batches';
                const method = isEdit ? 'PUT' : 'POST';

                const res = await apiFetch(url, {
                    method,
                    body: JSON.stringify(payload)
                });
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    return showSaveError(body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')'));
                }

                toast(isEdit ? 'Lote atualizado.' : 'Lote criado.');
                editModal.hide();
                await load(null);
            }

            function showSaveError(msg) {
                saveError.textContent = msg;
                saveError.classList.remove('d-none');
            }

            async function destroyBatch(id) {
                if (!confirm('Excluir lote #' + id + '?')) return;

                const res = await apiFetch('/api/v1/awards/batches/' + id, {
                    method: 'DELETE'
                });
                if (!res.ok) return toast('Falha ao excluir (' + res.status + ')', 'danger');

                toast('Lote excluído.');
                await load(null);
            }

            async function publishBatch(id) {
                const res = await apiFetch(`/api/v1/awards/batches/${id}/publish`, {
                    method: 'POST'
                });
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    return toast('Falha ao publicar: ' + (body ? JSON.stringify(body) : res.status), 'danger');
                }
                toast('Lote publicado.');
                await load(null);
            }

            async function archiveBatch(id) {
                const res = await apiFetch(`/api/v1/awards/batches/${id}/archive`, {
                    method: 'POST'
                });
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    return toast('Falha ao arquivar: ' + (body ? JSON.stringify(body) : res.status), 'danger');
                }
                toast('Lote arquivado.');
                await load(null);
            }

            // ===== Resultados (sync) =====
            function renderLinkedResults() {
                const items = Array.from(linkedMap.values());
                if (!items.length) {
                    linkedResultsTbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Nenhum resultado.</td></tr>`;
                    return;
                }

                linkedResultsTbody.innerHTML = items.map(it => `
      <tr>
        <td>
          <div class="fw-semibold">${it.slot?.title || ('Slot #' + it.slot_id)}</div>
          <div class="text-muted small">${it.slot?.provider || ''} ${it.slot?.provider_game_id ? '• ' + it.slot.provider_game_id : ''}</div>
        </td>
        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-prize-initial data-slot-id="${it.slot_id}" value="${it.prize_sum_initial ?? ''}"></td>
        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-prize-final data-slot-id="${it.slot_id}" value="${it.prize_sum_final ?? ''}"></td>
        <td><input type="number" min="1" class="form-control form-control-sm" data-prize-interval data-slot-id="${it.slot_id}" value="${it.increment_interval_minutes ?? ''}"></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" data-remove data-slot-id="${it.slot_id}">Remover</button>
        </td>
      </tr>
    `).join('');
            }

            async function openResultsModal(batchId) {
                managingBatchId = String(batchId);
                syncError.classList.add('d-none');
                syncError.textContent = '';
                linkedMap.clear();
                linkedResultsTbody.innerHTML = `<tr><td colspan="8" class="text-muted p-4">Carregando…</td></tr>`;

                const res = await apiFetch('/api/v1/awards/batches/' + batchId);
                if (!res.ok) return toast('Falha ao carregar lote (' + res.status + ')', 'danger');

                const batch = await res.json();
                resultsSubtitle.innerHTML = `${batch.title} • ${badge(batch.vertical)} • ${badge(batch.status)}`;

                (batch.results || []).forEach(r => {
                    linkedMap.set(String(r.slot_id), {
                        slot_id: r.slot_id,
                        prize_sum_initial: r.prize_sum_initial ?? null,
                        prize_sum_final: r.prize_sum_final ?? null,
                        increment_interval_minutes: r.increment_interval_minutes ?? null,
                        meta: r.meta ?? null,
                        slot: r.slot || null,
                    });
                });

                renderLinkedResults();
                slotResultsTbody.innerHTML =
                    `<tr><td colspan="3" class="text-muted p-3">Faça uma busca para adicionar.</td></tr>`;
                document.getElementById('slotSearch').value = '';
                resultsModal.show();
            }

            async function searchSlots() {
                const q = document.getElementById('slotSearch').value.trim();
                if (!q) return;

                slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-3">Buscando…</td></tr>`;
                const params = new URLSearchParams({
                    q
                });
                const res = await apiFetch('/api/v1/slots?' + params.toString());
                if (!res.ok) {
                    slotResultsTbody.innerHTML =
                        `<tr><td colspan="3" class="text-danger p-3">Erro ao buscar slots (${res.status}).</td></tr>`;
                    return;
                }

                const data = await res.json();
                const rows = data.data || [];
                if (!rows.length) {
                    slotResultsTbody.innerHTML =
                        `<tr><td colspan="3" class="text-muted p-3">Nenhum slot encontrado.</td></tr>`;
                    return;
                }

                slotResultsTbody.innerHTML = rows.slice(0, 12).map(s => `
      <tr>
        <td>
          <div class="fw-semibold">${s.title}</div>
          <div class="text-muted small">${s.provider} • ${s.provider_game_id} • ${s.status}</div>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" data-add data-slot-id="${s.id}">Adicionar</button>
        </td>
      </tr>
    `).join('');
            }

            function addResult(slotId, slotObj) {
                const key = String(slotId);
                if (linkedMap.has(key)) return toast('Esse slot já está no lote.', 'secondary');

                linkedMap.set(key, {
                    slot_id: Number(slotId),
                    position: 0,
                    wins_count: 0,
                    prize_sum: 0,
                    max_prize: 0,
                    avg_prize: 0,
                    prize_sum_initial: null,
                    prize_sum_final: null,
                    increment_interval_minutes: null,
                    meta: null,
                    slot: slotObj || null,
                });
                renderLinkedResults();
            }

            async function syncResults() {
                syncError.classList.add('d-none');
                syncError.textContent = '';

                // lê inputs
                document.querySelectorAll('[data-prize-initial]').forEach(i => {
                    const k = i.dataset.slotId;
                    const it = linkedMap.get(k);
                    if (it) it.prize_sum_initial = i.value ? Number(i.value) : null;
                });
                document.querySelectorAll('[data-prize-final]').forEach(i => {
                    const k = i.dataset.slotId;
                    const it = linkedMap.get(k);
                    if (it) it.prize_sum_final = i.value ? Number(i.value) : null;
                });
                document.querySelectorAll('[data-prize-interval]').forEach(i => {
                    const k = i.dataset.slotId;
                    const it = linkedMap.get(k);
                    if (it) it.increment_interval_minutes = i.value ? Number(i.value) : null;
                });

                const items = Array.from(linkedMap.values()).map(it => ({
                    slot_id: it.slot_id,
                    prize_sum_initial: it.prize_sum_initial,
                    prize_sum_final: it.prize_sum_final,
                    increment_interval_minutes: it.increment_interval_minutes,
                    meta: it.meta ?? null,
                }));

                const res = await apiFetch(`/api/v1/awards/batches/${managingBatchId}/results`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        items
                    })
                });

                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    syncError.textContent = body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')');
                    syncError.classList.remove('d-none');
                    return;
                }

                toast('Resultados sincronizados.');
                resultsModal.hide();
                await load(null);
            }

            // ===== Wire up =====
            document.getElementById('btnNew').addEventListener('click', openNew);
            document.getElementById('btnReload').addEventListener('click', () => load(null));
            document.getElementById('btnSearch').addEventListener('click', () => load(null));
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                document.getElementById('vertical').value = '';
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

            document.getElementById('btnSave').addEventListener('click', saveBatch);

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'edit') openEdit(id);
                if (action === 'results') openResultsModal(id);
                if (action === 'publish') publishBatch(id);
                if (action === 'archive') archiveBatch(id);
                if (action === 'delete') destroyBatch(id);
            });

            document.getElementById('btnSlotSearch').addEventListener('click', searchSlots);
            document.getElementById('slotSearch').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchSlots();
                }
            });

            slotResultsTbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-add]');
                if (!btn) return;

                const slotId = btn.getAttribute('data-slot-id');
                const row = btn.closest('tr');

                const title = row.querySelector('.fw-semibold')?.textContent?.trim() || '';
                const meta = row.querySelector('.text-muted')?.textContent?.trim() || '';

                addResult(slotId, {
                    id: Number(slotId),
                    title,
                    provider: meta
                });
            });

            linkedResultsTbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-remove]');
                if (!btn) return;
                const slotId = btn.getAttribute('data-slot-id');
                linkedMap.delete(String(slotId));
                renderLinkedResults();
            });

            document.getElementById('btnSyncResults').addEventListener('click', syncResults);

            // Init
            document.addEventListener('DOMContentLoaded', () => {
                load(null);
            });

            // Expose to window for inline onclick
            window.openEdit = openEdit;
        </script>
    @endpush
@endsection
