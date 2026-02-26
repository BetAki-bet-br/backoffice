@extends('layouts.app')

@section('title', 'Top Lists - Backoffice')
@section('page-title', 'Top Lists')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Top Lists</h2>
    <div class="text-muted small">CRUD via API v1 • publish • validade • criteria • sync slots</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Nova top list</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-6">
        <label class="form-label">Busca</label>
        <input type="text" class="form-control" id="q" placeholder="Título / slug">
      </div>

      <div class="col-6 col-lg-3">
        <label class="form-label">Status</label>
        <select class="form-select" id="status">
          <option value="">Todos</option>
          <option value="draft">draft</option>
          <option value="published">published</option>
          <option value="archived">archived</option>
          <option value="scheduled">scheduled</option>
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
          <th>Top List</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 170px;">Validade</th>
          <th style="width: 110px;">Slots</th>
          <th style="width: 420px;" class="text-end">Ações</th>
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

{{-- Modal Create/Edit TopList --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTitle">Top List</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <label class="form-label">Título</label>
            <input class="form-control" id="f_title" placeholder="Ex: Top vencedores da semana">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Slug (opcional)</label>
            <input class="form-control" id="f_slug" placeholder="Deixe vazio para gerar automaticamente">
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="draft">draft</option>
              <option value="published">published</option>
              <option value="archived">archived</option>
              <option value="scheduled">scheduled</option>
            </select>
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Vertical</label>
            <select class="form-select" id="f_vertical">
                <option value="slots">slots</option>
                <option value="live">live</option>
            </select>
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Type</label>
            <select class="form-select" id="f_type">
                <option value="manual">manual</option>
                <option value="auto">auto</option>
            </select>
          </div>

   

          <div class="col-12 col-lg-3">
            <label class="form-label">Valid from</label>
            <input type="date" class="form-control" id="f_valid_from">
          </div>

          <div class="col-12 col-lg-3">
            <label class="form-label">Valid until</label>
            <input type="date" class="form-control" id="f_valid_until">
          </div>

          <div class="col-12">
            <label class="form-label">Criteria (JSON)</label>
            <textarea class="form-control font-monospace" rows="10" id="f_criteria" spellcheck="false">{}</textarea>
            <div class="form-text">
              Sua request suporta <code>criteria.countries</code>, <code>criteria.provider</code>, <code>criteria.tags</code>.
              Para type=manual, pode ficar vazio.
            </div>
          </div>

          <div class="col-12">
            <div class="alert alert-info small mb-0">
              <strong>Slots:</strong> use o botão <em>Slots</em> na tabela depois de salvar.
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

{{-- Modal Sync Slots --}}
<div class="modal fade" id="slotsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="slotsTitle">Slots da Top List</h5>
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
                      <th style="width: 40px;"></th>
                      <th>Slot</th>
                      <th style="width: 140px;">Posição</th>
                      <th style="width: 90px;" class="text-end">Remover</th>
                    </tr>
                  </thead>
                  <tbody id="linkedSlots">
                    <tr><td colspan="4" class="text-muted p-4">Carregando…</td></tr>
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
  const tbody = document.getElementById('tbody');
  const info = document.getElementById('paginationInfo');

  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const editTitle = document.getElementById('editTitle');
  const saveError = document.getElementById('saveError');

  const slotsModal = new bootstrap.Modal(document.getElementById('slotsModal'));
  const slotsSubtitle = document.getElementById('slotsSubtitle');
  const linkedSlotsTbody = document.getElementById('linkedSlots');
  const slotResultsTbody = document.getElementById('slotResults');
  const syncError = document.getElementById('syncError');

  let nextCursor = null;
  let prevCursor = null;

  let editingId = null;
  let managingTopListId = null;

  const cacheById = new Map();
  const linkedMap = new Map(); // slot_id -> { slot_id, position, slot }

  function isoToInputDate(dt) {
    if (!dt) return '';
    // Extracts only the date part (YYYY-MM-DD) from an ISO string
    return dt.substring(0, 10);
  }

  function render(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhuma top list encontrada.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(item => `
      <tr>
        <td class="text-muted">#${item.id}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(item.title || '—')}</div>
        </td>
        <td>${badge(item.status)}</td>
        <td class="text-muted small text-nowrap">${fmtDate(item.valid_from)} → ${fmtDate(item.valid_until)}</td>
        <td class="text-muted">${Array.isArray(item.slots) ? item.slots.length : (item.slots_count ?? '—')}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${item.id}">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-action="slots" data-id="${item.id}">Slots</button>
            <button class="btn btn-sm btn-outline-success" data-action="publish" data-id="${item.id}">Publicar</button>
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
    };
  }

  async function load(cursor = null) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>`;

    const params = new URLSearchParams();
    if (cursor) params.set('cursor', cursor);

    const { q, status } = getFilters();
    if (q) params.set('q', q);
    if (status) params.set('status', status);

    const res = await apiFetch('/api/v1/top-lists?' + params.toString());
    if (!res.ok) {
      toast('Falha ao carregar top lists (' + res.status + ')', 'danger');
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

  function fillForm(item) {
    document.getElementById('f_title').value = item?.title || '';
    document.getElementById('f_slug').value = item?.slug || '';
    document.getElementById('f_status').value = item?.status || 'draft';
    document.getElementById('f_vertical').value = item?.vertical || 'slots';
    document.getElementById('f_type').value = item?.type || 'manual';
    document.getElementById('f_valid_from').value = isoToInputDate(item?.valid_from);
    document.getElementById('f_valid_until').value = isoToInputDate(item?.valid_until);
    document.getElementById('f_criteria').value = JSON.stringify(item?.criteria || {}, null, 2);
  }

  function buildPayload() {
    let criteria = {};
    try { criteria = JSON.parse(document.getElementById('f_criteria').value || '{}'); } catch { criteria = {}; }

    const slug = document.getElementById('f_slug').value.trim();

    return {
      title: document.getElementById('f_title').value.trim(),
      slug: slug ? slug : null,
      status: document.getElementById('f_status').value,
      vertical: document.getElementById('f_vertical').value.trim() || null,
      type: document.getElementById('f_type').value,
      valid_from: document.getElementById('f_valid_from').value || null,
      valid_until: document.getElementById('f_valid_until').value || null,
      criteria,
    };
  }

  function openNew() {
    editingId = null;
    editTitle.textContent = 'Nova Top List';
    saveError.classList.add('d-none');
    saveError.textContent = '';
    fillForm({ title: '', slug: '', status: 'draft', vertical: 'slots', type: 'manual', position: 0, valid_from: null, valid_until: null, criteria: {} });
    editModal.show();
  }

  async function openEdit(id) {
    editingId = String(id);
    editTitle.textContent = 'Editar Top List #' + id;
    saveError.classList.add('d-none');
    saveError.textContent = '';

    const res = await apiFetch('/api/v1/top-lists/' + id);
    if (!res.ok) {
      toast('Falha ao buscar top list (' + res.status + ')', 'danger');
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

    const isEdit = !!editingId;
    const url = isEdit ? ('/api/v1/top-lists/' + editingId) : '/api/v1/top-lists';
    const method = isEdit ? 'PUT' : 'POST';

    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });

    if (!res.ok) {
      await showApiError(saveError, res, 'Erro ao salvar top list');
      return;
    }

    toast(isEdit ? 'Top list atualizada.' : 'Top list criada.');
    editModal.hide();
    await load(null);
  }

  async function destroyTopList(id) {
    if (!confirm('Excluir top list #' + id + '?')) return;
    const res = await apiFetch('/api/v1/top-lists/' + id, { method: 'DELETE' });
    if (!res.ok) {
      await toastApiError(res, 'excluir top list');
      return;
    }
    toast('Top list excluída.');
    await load(null);
  }

  async function publish(id) {
    const res = await apiFetch('/api/v1/top-lists/' + id + '/publish', { method: 'POST' });
    if (!res.ok) {
      await toastApiError(res, 'publicar top list');
      return;
    }
    toast('Top list publicada.');
    await load(null);
  }

  // ===== Slots Sync =====
  let sortableInstance = null;

  function renderLinkedSlots() {
    const items = Array.from(linkedMap.values()).sort((a,b) => (a.position ?? 0) - (b.position ?? 0));
    
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
          <div class="fw-semibold">${escapeHtml(it.slot?.title || ('Slot #' + it.slot_id))}</div>
          <div class="text-muted small">${escapeHtml(it.slot?.provider || '')} ${it.slot?.provider_game_id ? '• '+escapeHtml(it.slot.provider_game_id) : ''}</div>
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
    if (sortableInstance) {
        sortableInstance.destroy();
    }
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
    const items = Array.from(linkedMap.values()).sort((a,b) => (a.position ?? 0) - (b.position ?? 0));
    const idx = items.findIndex(it => String(it.slot_id) === String(id));
    if (idx === -1) return;

    const newIdx = idx + direction;
    if (newIdx < 0 || newIdx >= items.length) return;

    const moved = items.splice(idx, 1)[0];
    items.splice(newIdx, 0, moved);
    
    items.forEach((it, i) => {
        if (linkedMap.has(String(it.slot_id))) {
            linkedMap.get(String(it.slot_id)).position = i;
        }
    });
    
    renderLinkedSlots();
  }

  async function openSlotsModal(id) {
    managingTopListId = String(id);
    syncError.classList.add('d-none');
    syncError.textContent = '';
    linkedMap.clear();
    linkedSlotsTbody.innerHTML = `<tr><td colspan="4" class="text-muted p-4">Carregando…</td></tr>`;

    const res = await apiFetch('/api/v1/top-lists/' + id);
    if (!res.ok) {
      toast('Falha ao carregar top list (' + res.status + ')', 'danger');
      return;
    }

    const top = await res.json();
    slotsSubtitle.innerHTML = `${escapeHtml(top.title)} • ${escapeHtml(top.slug || '')} • ${badge(top.vertical)}`;

    (top.slots || []).forEach(s => {
      linkedMap.set(String(s.id), {
        slot_id: s.id,
        position: s.pivot?.position ?? 0,
        slot: { id: s.id, title: s.title, provider: s.provider, provider_game_id: s.provider_game_id, status: s.status }
      });
    });

    renderLinkedSlots();
    initSortable();

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

    const items = Array.from(linkedMap.values())
        .sort((a,b) => (a.position ?? 0) - (b.position ?? 0))
        .map((it, idx) => ({
            slot_id: it.slot_id,
            position: idx,
        }));

    const res = await apiFetch(`/api/v1/top-lists/${managingTopListId}/slots`, {
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

  // ===== Events =====
  document.getElementById('btnNew').addEventListener('click', openNew);
  document.getElementById('btnReload').addEventListener('click', () => load(null));
  document.getElementById('btnSearch').addEventListener('click', () => load(null));
  document.getElementById('q').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); load(null); }
  });
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
    if (action === 'slots') openSlotsModal(id);
    if (action === 'publish') publish(id);
    if (action === 'delete') destroyTopList(id);
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