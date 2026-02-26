@extends('layouts.app')

@section('title', 'Showcases - Backoffice')
@section('page-title', 'Showcases')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Showcases</h2>
    <div class="text-muted small">CRUD via API v1 • type: manual/dynamic • slots apenas em manual</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Novo showcase</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-5">
        <label class="form-label">Busca</label>
        <input type="text" class="form-control" id="q" placeholder="Título / slug">
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Status</label>
        <select class="form-select" id="status">
          <option value="">Todos</option>
          <option value="active">active</option>
          <option value="inactive">inactive</option>
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Type</label>
        <select class="form-select" id="type">
          <option value="">Todos</option>
          <option value="manual">manual</option>
          <option value="dynamic">dynamic</option>
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
          <th>Showcase</th>
          <th style="width: 110px;">Posição</th>
          <th style="width: 140px;">Type</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 120px;">Slots</th>
          <th style="width: 340px;" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>
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

{{-- Modal Create/Edit Showcase --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTitle">Showcase</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <label class="form-label">Título</label>
            <input class="form-control" id="f_title" placeholder="Ex: Destaques da Semana">
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

          <div class="col-6 col-lg-4">
            <label class="form-label">Type</label>
            <select class="form-select" id="f_type">
              <option value="manual">manual</option>
              <option value="dynamic">dynamic</option>
            </select>
            <div class="form-text">manual: slots fixos • dynamic: usa filters</div>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Posição</label>
            <input type="number" min="0" class="form-control" id="f_position" placeholder="0">
          </div>

          <div class="col-12">
            <label class="form-label">Filters (JSON)</label>
            <textarea class="form-control font-monospace" rows="10" id="f_filters" spellcheck="false">{}</textarea>
            <div class="form-text">
              Para type=dynamic, preencha filtros aqui. Para manual, pode ficar vazio.
            </div>
          </div>

          <div class="col-12">
            <div class="alert alert-info small mb-0">
              <strong>Slots:</strong> só podem ser configurados em vitrines <code>manual</code>.
              Use o botão <em>Slots</em> na tabela depois de salvar.
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

{{-- Modal Sync Slots (manual) --}}
<div class="modal fade" id="slotsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="slotsTitle">Slots do Showcase</h5>
          <div class="text-muted small" id="slotsSubtitle">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-5">
            <label class="form-label">Buscar slot (API /slots?q=)</label>
            <div class="input-group">
              <input class="form-control" id="slotSearch" placeholder="Ex: gates, pragmatic, game_id...">
              <button class="btn btn-outline-secondary" type="button" id="btnSlotSearch">Buscar</button>
            </div>

            <div class="mt-3 table-soft">
              <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                  <thead>
                    <tr>
                      <th>Resultado</th>
                      <th style="width: 110px;">Posição</th>
                      <th style="width: 90px;" class="text-end">Add</th>
                    </tr>
                  </thead>
                  <tbody id="slotResults">
                    <tr><td colspan="3" class="text-muted p-3">Faça uma busca para adicionar slots.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label mb-0">Slots vinculados</label>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="btnSortByPosition">
                Ordenar por posição
              </button>
            </div>

            <div class="mt-2 table-soft">
              <div class="table-responsive">
                <table class="table mb-0 align-middle">
                  <thead>
                    <tr>
                      <th>Slot</th>
                      <th style="width: 140px;">Posição</th>
                      <th style="width: 90px;" class="text-end">Remover</th>
                    </tr>
                  </thead>
                  <tbody id="linkedSlots">
                    <tr><td colspan="3" class="text-muted p-4">Carregando…</td></tr>
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
<script>
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
  let managingShowcaseId = null;

  // cache lista
  const cacheById = new Map();

  // slots vinculados
  const linkedMap = new Map(); // slot_id -> { slot_id, position, slot }

  function render(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(item => `
      <tr>
        <td class="text-muted">#${item.id}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(item.title || '—')}</div>
          <div class="text-muted small">${escapeHtml(item.slug || '')}</div>
        </td>
        <td class="text-muted">${item.position ?? '—'}</td>
        <td>${badgeType(item.type)}</td>
        <td>${badgeStatus(item.status)}</td>
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
      type: document.getElementById('type').value.trim(),
    };
  }

  async function load(cursor = null) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>`;

    const params = new URLSearchParams();
    if (cursor) params.set('cursor', cursor);

    const { q, status, type } = getFilters();
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    // se sua API ainda não filtrar por type, isso é só UI (não quebra)
    if (type) params.set('type', type);

    const res = await apiFetch('/api/v1/showcases?' + params.toString());
    if (!res.ok) {
      toast('Falha ao carregar showcases (' + res.status + ')', 'danger');
      tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-4">Erro ao carregar.</td></tr>`;
      return;
    }

    const data = await res.json();
    const rows = data.data || [];

    cacheById.clear();
    rows.forEach(r => cacheById.set(String(r.id), r));

    nextCursor = data.next_cursor || null;
    prevCursor = data.prev_cursor || null;

    info.textContent = `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;
    render(rows);
  }

  // ===== CRUD =====
  function fillForm(item) {
    document.getElementById('f_title').value = item?.title || '';
    document.getElementById('f_slug').value = item?.slug || '';
    document.getElementById('f_status').value = item?.status || 'active';
    document.getElementById('f_type').value = item?.type || 'manual';
    document.getElementById('f_position').value = (item?.position ?? '') === null ? '' : (item?.position ?? '');
    document.getElementById('f_filters').value = JSON.stringify(item?.filters || {}, null, 2);
  }

  function buildPayload() {
    const positionRaw = document.getElementById('f_position').value;
    const position = positionRaw === '' ? null : Number(positionRaw);

    let filters = {};
    try { filters = JSON.parse(document.getElementById('f_filters').value || '{}'); } catch { filters = {}; }

    const slug = document.getElementById('f_slug').value.trim();

    return {
      title: document.getElementById('f_title').value.trim(),
      slug: slug ? slug : null,
      status: document.getElementById('f_status').value,
      type: document.getElementById('f_type').value,
      position,
      filters,
    };
  }

  function openNew() {
    editingId = null;
    editTitle.textContent = 'Novo Showcase';
    saveError.classList.add('d-none');
    saveError.textContent = '';
    fillForm({ title: '', slug: '', status: 'active', type: 'manual', position: 0, filters: {} });
    editModal.show();
  }

  async function openEdit(id) {
    editingId = String(id);
    editTitle.textContent = 'Editar Showcase #' + id;
    saveError.classList.add('d-none');
    saveError.textContent = '';

    const res = await apiFetch('/api/v1/showcases/' + id);
    if (!res.ok) {
      toast('Falha ao buscar showcase (' + res.status + ')', 'danger');
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
    if (!payload.title) {
      saveError.textContent = 'Título é obrigatório.';
      saveError.classList.remove('d-none');
      return;
    }
    if (!['active','inactive'].includes(payload.status)) {
      saveError.textContent = 'Status inválido. Use active/inactive.';
      saveError.classList.remove('d-none');
      return;
    }
    if (!['manual','dynamic'].includes(payload.type)) {
      saveError.textContent = 'Type inválido. Use manual/dynamic.';
      saveError.classList.remove('d-none');
      return;
    }

    const isEdit = !!editingId;
    const url = isEdit ? ('/api/v1/showcases/' + editingId) : '/api/v1/showcases';
    const method = isEdit ? 'PUT' : 'POST';

    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (!res.ok) {
      await showApiError(saveError, res, 'Erro ao salvar showcase');
      return;
    }

    toast(isEdit ? 'Showcase atualizado.' : 'Showcase criado.');
    editModal.hide();
    await load(null);
  }

  async function destroyShowcase(id) {
    if (!confirm('Excluir showcase #' + id + '?')) return;
    const res = await apiFetch('/api/v1/showcases/' + id, { method: 'DELETE' });
    if (!res.ok) {
      await toastApiError(res, 'excluir showcase');
      return;
    }
    toast('Showcase excluído.');
    await load(null);
  }

  // ===== Slots Sync (manual) =====
  function renderLinkedSlots() {
    const items = Array.from(linkedMap.values());
    if (!items.length) {
      linkedSlotsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-4">Nenhum slot vinculado.</td></tr>`;
      return;
    }

    linkedSlotsTbody.innerHTML = items.map(it => `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(it.slot?.title || ('Slot #' + it.slot_id))}</div>
          <div class="text-muted small">${escapeHtml(it.slot?.provider || '')} ${it.slot?.provider_game_id ? '• '+escapeHtml(it.slot.provider_game_id) : ''}</div>
        </td>
        <td>
          <input type="number" min="0" class="form-control form-control-sm" data-pos data-slot-id="${it.slot_id}" value="${it.position ?? 0}">
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" data-remove-slot data-slot-id="${it.slot_id}">Remover</button>
        </td>
      </tr>
    `).join('');
  }

  async function openSlotsModal(showcaseId) {
    managingShowcaseId = String(showcaseId);
    syncError.classList.add('d-none');
    syncError.textContent = '';
    linkedMap.clear();
    linkedSlotsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-4">Carregando…</td></tr>`;

    const res = await apiFetch('/api/v1/showcases/' + showcaseId);
    if (!res.ok) {
      toast('Falha ao carregar showcase (' + res.status + ')', 'danger');
      return;
    }

    const showcase = await res.json();

    if ((showcase.type || '').toLowerCase() !== 'manual') {
      toast('Apenas vitrines manuais podem ter slots.', 'danger');
      return;
    }

    slotsTitle.textContent = 'Slots do Showcase';
    slotsSubtitle.textContent = `${showcase.title} • ${showcase.slug || ''}`;

    (showcase.slots || []).forEach(s => {
      linkedMap.set(String(s.id), {
        slot_id: s.id,
        position: s.pivot?.position ?? 0,
        slot: { id: s.id, title: s.title, provider: s.provider, provider_game_id: s.provider_game_id, status: s.status }
      });
    });

    renderLinkedSlots();
    slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-3">Faça uma busca para adicionar slots.</td></tr>`;
    document.getElementById('slotSearch').value = '';

    slotsModal.show();
  }

  async function searchSlots() {
    const q = document.getElementById('slotSearch').value.trim();
    if (!q) {
      slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-3">Digite algo para buscar.</td></tr>`;
      return;
    }

    slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-3">Buscando…</td></tr>`;

    const params = new URLSearchParams({ q });
    const res = await apiFetch('/api/v1/slots?' + params.toString());
    if (!res.ok) {
      slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-danger p-3">Erro ao buscar slots (${res.status}).</td></tr>`;
      return;
    }

    const data = await res.json();
    const rows = data.data || [];

    if (!rows.length) {
      slotResultsTbody.innerHTML = `<tr><td colspan="3" class="text-muted p-3">Nenhum slot encontrado.</td></tr>`;
      return;
    }

    slotResultsTbody.innerHTML = rows.slice(0, 12).map(s => `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(s.title || '')}</div>
          <div class="text-muted small">${escapeHtml(s.provider || '')} • ${escapeHtml(s.provider_game_id || '')} • ${escapeHtml(s.status || '')}</div>
        </td>
        <td>
          <input type="number" min="0" class="form-control form-control-sm" data-add-pos value="0">
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" data-add-slot data-slot-id="${s.id}">Adicionar</button>
        </td>
      </tr>
    `).join('');
  }

  function addSlotToLinked(slotId, position, slotObj = null) {
    const key = String(slotId);
    if (linkedMap.has(key)) {
      toast('Esse slot já está vinculado.', 'secondary');
      return;
    }
    linkedMap.set(key, { slot_id: Number(slotId), position: Number(position) || 0, slot: slotObj });
    renderLinkedSlots();
  }

  async function syncSlots() {
    syncError.classList.add('d-none');
    syncError.textContent = '';

    document.querySelectorAll('[data-pos]').forEach(inp => {
      const slotId = inp.getAttribute('data-slot-id');
      const it = linkedMap.get(String(slotId));
      if (it) it.position = Number(inp.value || 0);
    });

    const items = Array.from(linkedMap.values()).map(it => ({
      slot_id: it.slot_id,
      position: it.position ?? 0,
    }));

    const res = await apiFetch(`/api/v1/showcases/${managingShowcaseId}/slots`, {
      method: 'PUT',
      body: JSON.stringify({ items })
    });

    if (!res.ok) {
      await showApiError(syncError, res, 'Erro ao salvar slots');
      return;
    }

    toast('Slots sincronizados.');
    slotsModal.hide();
    await load(null);
  }

  // ===== Eventos =====
  document.getElementById('btnNew').addEventListener('click', openNew);
  document.getElementById('btnReload').addEventListener('click', () => load(null));
  document.getElementById('btnSearch').addEventListener('click', () => load(null));
  document.getElementById('q').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); load(); }
  });
  document.getElementById('btnClear').addEventListener('click', () => {
    document.getElementById('q').value = '';
    document.getElementById('status').value = '';
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

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');

    if (action === 'edit') openEdit(id);
    if (action === 'slots') openSlotsModal(id);
    if (action === 'delete') destroyShowcase(id);
  });

  document.getElementById('btnSlotSearch').addEventListener('click', searchSlots);
  document.getElementById('slotSearch').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); searchSlots(); }
  });

  slotResultsTbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-add-slot]');
    if (!btn) return;

    const slotId = btn.getAttribute('data-slot-id');
    const row = btn.closest('tr');
    const posInput = row.querySelector('[data-add-pos]');
    const position = posInput ? posInput.value : 0;

    const title = row.querySelector('.fw-semibold')?.textContent?.trim() || '';
    const meta = row.querySelector('.text-muted')?.textContent?.trim() || '';
    addSlotToLinked(slotId, position, { id: Number(slotId), title, provider: meta });
  });

  linkedSlotsTbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-remove-slot]');
    if (!btn) return;

    const slotId = btn.getAttribute('data-slot-id');
    linkedMap.delete(String(slotId));
    renderLinkedSlots();
  });

  document.getElementById('btnSortByPosition').addEventListener('click', () => {
    const arr = Array.from(linkedMap.values()).sort((a,b) => (a.position ?? 0) - (b.position ?? 0));
    linkedMap.clear();
    arr.forEach(it => linkedMap.set(String(it.slot_id), it));
    renderLinkedSlots();
  });

  document.getElementById('btnSyncSlots').addEventListener('click', syncSlots);

  // init
  load(null);
</script>
@endpush
@endsection