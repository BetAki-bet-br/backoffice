@extends('layouts.app')

@section('title', 'Top Winners - Backoffice')
@section('page-title', 'Top Winners')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Lotes de Top Winners</h2>
    <div class="text-muted small">CRUD via API v1 • cursor pagination • sync winners • publish/archive</div>
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
        <input type="text" class="form-control" id="q" placeholder="Ex: Top winners semana 50">
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
          <th style="width: 110px;">Top N</th>
          <th style="width: 440px;" class="text-end">Ações</th>
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
            <input class="form-control" id="f_title" placeholder="Ex: Top winners da semana">
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
            <div class="form-text">Publish exige status=review + winners.</div>
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Período início</label>
            <input type="date" class="form-control" id="f_period_start">
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Período fim</label>
            <input type="date" class="form-control" id="f_period_end">
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Top N</label>
            <input type="number" min="1" max="100" class="form-control" id="f_top_n" placeholder="10">
          </div>

          <div class="col-12">
            <label class="form-label">Criteria (JSON)</label>
            <textarea class="form-control font-monospace" rows="10" id="f_criteria" spellcheck="false">{}</textarea>
            <div class="form-text">
              Aceita: min_prize, countries[], provider, tags[].
            </div>
          </div>

          <div class="col-12">
            <div class="alert alert-info small mb-0">
              <strong>Winners:</strong> use o botão <em>Winners</em> na tabela depois de salvar.
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

{{-- Modal Sync Winners --}}
<div class="modal fade" id="winnersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xxl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Winners do lote</h5>
          <div class="text-muted small" id="winnersSubtitle">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
          <div class="text-muted small">
            <strong>Dica:</strong> <code>display_name</code> é obrigatório e deve estar anonimizado (ex: "Jogador #1234").
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnSortByRank">Ordenar por rank</button>
            <button class="btn btn-sm btn-outline-primary" type="button" id="btnAddRow">Adicionar linha</button>
          </div>
        </div>

        <div class="table-soft">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead>
                <tr>
                  <th style="min-width: 220px;">Display name *</th>
                  <th style="width: 180px;">Player ref</th>
                  <th style="width: 90px;">Country</th>
                  <th style="width: 80px;">Rank *</th>
                  <th style="width: 110px;">Posição</th>
                  <th style="width: 120px;">Wins</th>
                  <th style="width: 140px;">Prize sum</th>
                  <th style="width: 140px;">Max</th>
                  <th style="width: 140px;">Avg</th>
                  <th style="width: 240px;">Meta (JSON)</th>
                  <th style="width: 90px;" class="text-end">Remover</th>
                </tr>
              </thead>
              <tbody id="linkedWinners">
                <tr><td colspan="11" class="text-muted p-4">Carregando…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="alert alert-danger d-none mt-3 small" id="syncError"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
        <button class="btn btn-primary" id="btnSyncWinners">Salvar winners</button>
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

  const winnersModal = new bootstrap.Modal(document.getElementById('winnersModal'));
  const winnersSubtitle = document.getElementById('winnersSubtitle');
  const linkedWinnersTbody = document.getElementById('linkedWinners');
  const syncError = document.getElementById('syncError');

  let nextCursor = null;
  let prevCursor = null;

  let editingId = null;
  let managingBatchId = null;

  // Usamos rank como chave “principal”, mas permitimos duplicado até validar antes de enviar.
  const rows = []; // array de objetos winners

  function badge(status) {
    const s = (status || '').toLowerCase();
    const map = {
      published: 'bg-success-subtle text-success',
      review: 'bg-info-subtle text-info',
      draft: 'bg-warning-subtle text-warning',
      archived: 'bg-secondary-subtle text-secondary',
    };
    const cls = map[s] || 'bg-light text-muted';
    return `<span class="badge badge-status ${cls}">${status || '—'}</span>`;
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

    const { q, vertical, status } = getFilters();
    if (q) params.set('q', q);
    if (vertical) params.set('vertical', vertical);
    if (status) params.set('status', status);

    const res = await apiFetch('/api/v1/winners/batches?' + params.toString());
    if (!res.ok) {
      toast('Falha ao carregar lotes (' + res.status + ')', 'danger');
      tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-4">Erro ao carregar.</td></tr>`;
      return;
    }

    const data = await res.json();
    const list = data.data || [];

    nextCursor = data.next_cursor || null;
    prevCursor = data.prev_cursor || null;

    info.textContent = `Carregados: ${list.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;

    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(b => `
      <tr>
        <td class="text-muted">#${b.id}</td>
        <td>
          <div class="fw-semibold">${b.title || '—'}</div>
          <div class="text-muted small">${b.period_start || '—'} → ${b.period_end || '—'}</div>
        </td>
        <td class="text-muted">${b.vertical || '—'}</td>
        <td>${badge(b.status)}</td>
        <td class="text-muted small">${b.period_start || '—'} → ${b.period_end || '—'}</td>
        <td class="text-muted">${b.top_n ?? '—'}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${b.id}">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-action="winners" data-id="${b.id}">Winners</button>
            <button class="btn btn-sm btn-outline-success" data-action="publish" data-id="${b.id}">Publicar</button>
            <button class="btn btn-sm btn-outline-warning" data-action="archive" data-id="${b.id}">Arquivar</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${b.id}">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function fillForm(batch) {
    document.getElementById('f_title').value = batch?.title || '';
    document.getElementById('f_vertical').value = batch?.vertical || 'slots';
    document.getElementById('f_status').value = batch?.status || 'draft';
    document.getElementById('f_period_start').value = batch?.period_start || '';
    document.getElementById('f_period_end').value = batch?.period_end || '';
    document.getElementById('f_top_n').value = batch?.top_n ?? 10;
    document.getElementById('f_criteria').value = JSON.stringify(batch?.criteria || {}, null, 2);
  }

  function buildPayload() {
    let criteria = {};
    try { criteria = JSON.parse(document.getElementById('f_criteria').value || '{}'); } catch { criteria = {}; }

    return {
      title: document.getElementById('f_title').value.trim(),
      vertical: document.getElementById('f_vertical').value,
      status: document.getElementById('f_status').value,
      period_start: document.getElementById('f_period_start').value,
      period_end: document.getElementById('f_period_end').value,
      top_n: Number(document.getElementById('f_top_n').value || 10),
      criteria,
    };
  }

  function openNew() {
    editingId = null;
    editTitle.textContent = 'Novo lote';
    saveError.classList.add('d-none'); saveError.textContent = '';
    fillForm({ title:'', vertical:'slots', status:'draft', period_start:'', period_end:'', top_n:10, criteria:{} });
    editModal.show();
  }

  async function openEdit(id) {
    editingId = String(id);
    editTitle.textContent = 'Editar lote #' + id;
    saveError.classList.add('d-none'); saveError.textContent = '';

    const res = await apiFetch('/api/v1/winners/batches/' + id);
    if (!res.ok) return toast('Falha ao buscar lote (' + res.status + ')', 'danger');

    const batch = await res.json();
    fillForm(batch);
    editModal.show();
  }

  function showSaveError(msg){
    saveError.textContent = msg;
    saveError.classList.remove('d-none');
  }

  async function saveBatch() {
    saveError.classList.add('d-none'); saveError.textContent = '';

    const payload = buildPayload();
    const allowedVertical = ['slots','live'];
    const allowedStatus = ['draft','review','published','archived'];

    if (!payload.title) return showSaveError('Título é obrigatório.');
    if (!allowedVertical.includes(payload.vertical)) return showSaveError('Vertical inválida.');
    if (!allowedStatus.includes(payload.status)) return showSaveError('Status inválido.');
    if (!payload.period_start || !payload.period_end) return showSaveError('Período início e fim são obrigatórios.');
    if (!(payload.top_n >= 1 && payload.top_n <= 100)) return showSaveError('Top N deve ser entre 1 e 100.');

    const isEdit = !!editingId;
    const url = isEdit ? ('/api/v1/winners/batches/' + editingId) : '/api/v1/winners/batches';
    const method = isEdit ? 'PUT' : 'POST';

    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return showSaveError(body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')'));
    }

    toast(isEdit ? 'Lote atualizado.' : 'Lote criado.');
    editModal.hide();
    await load(null);
  }

  async function destroyBatch(id) {
    if (!confirm('Excluir lote #' + id + '?')) return;
    const res = await apiFetch('/api/v1/winners/batches/' + id, { method: 'DELETE' });
    if (!res.ok) return toast('Falha ao excluir (' + res.status + ')', 'danger');
    toast('Lote excluído.');
    await load(null);
  }

  async function publishBatch(id) {
    const res = await apiFetch(`/api/v1/winners/batches/${id}/publish`, { method: 'POST' });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return toast('Falha ao publicar: ' + (body ? JSON.stringify(body) : res.status), 'danger');
    }
    toast('Lote publicado.');
    await load(null);
  }

  async function archiveBatch(id) {
    const res = await apiFetch(`/api/v1/winners/batches/${id}/archive`, { method: 'POST' });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return toast('Falha ao arquivar: ' + (body ? JSON.stringify(body) : res.status), 'danger');
    }
    toast('Lote arquivado.');
    await load(null);
  }

  // ===== Winners (sync) =====
  function safeJson(str) {
    if (!str) return null;
    try { return JSON.parse(str); } catch { return null; }
  }

  function renderRows() {
    if (!rows.length) {
      linkedWinnersTbody.innerHTML = `<tr><td colspan="11" class="text-muted p-4">Nenhum winner.</td></tr>`;
      return;
    }

    linkedWinnersTbody.innerHTML = rows.map((it, idx) => `
      <tr data-idx="${idx}">
        <td><input class="form-control form-control-sm" data-display value="${it.display_name ?? ''}" placeholder="Jogador #1234"></td>
        <td><input class="form-control form-control-sm" data-playerref value="${it.player_ref ?? ''}" placeholder="hash/opaque id"></td>
        <td><input class="form-control form-control-sm text-uppercase" data-country value="${it.country ?? ''}" placeholder="BR" maxlength="2"></td>
        <td><input type="number" min="1" class="form-control form-control-sm" data-rank value="${it.rank ?? 1}"></td>
        <td><input type="number" min="0" class="form-control form-control-sm" data-position value="${it.position ?? 0}"></td>
        <td><input type="number" min="0" class="form-control form-control-sm" data-wins value="${it.wins_count ?? 0}"></td>
        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-sum value="${it.prize_sum ?? 0}"></td>
        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-max value="${it.max_prize ?? 0}"></td>
        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" data-avg value="${it.avg_prize ?? 0}"></td>
        <td><input class="form-control form-control-sm font-monospace" data-meta value="${it.meta ? JSON.stringify(it.meta) : ''}" placeholder='{"key":"value"}'></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" data-remove>Remover</button>
        </td>
      </tr>
    `).join('');
  }

  async function openWinnersModal(batchId) {
    managingBatchId = String(batchId);
    syncError.classList.add('d-none'); syncError.textContent = '';
    rows.length = 0;

    linkedWinnersTbody.innerHTML = `<tr><td colspan="11" class="text-muted p-4">Carregando…</td></tr>`;

    const res = await apiFetch('/api/v1/winners/batches/' + batchId);
    if (!res.ok) return toast('Falha ao carregar lote (' + res.status + ')', 'danger');

    const batch = await res.json();
    winnersSubtitle.textContent = `${batch.title} • ${batch.vertical} • ${batch.status}`;

    // o controller normalmente retorna winners (relation)
    (batch.winners || []).forEach(w => {
      rows.push({
        player_ref: w.player_ref ?? null,
        display_name: w.display_name ?? '',
        country: w.country ?? null,
        rank: w.rank ?? 1,
        position: w.position ?? 0,
        wins_count: w.wins_count ?? 0,
        prize_sum: w.prize_sum ?? 0,
        max_prize: w.max_prize ?? 0,
        avg_prize: w.avg_prize ?? 0,
        meta: w.meta ?? null,
      });
    });

    renderRows();
    winnersModal.show();
  }

  function addEmptyRow() {
    rows.push({
      player_ref: null,
      display_name: '',
      country: null,
      rank: rows.length + 1,
      position: 0,
      wins_count: 0,
      prize_sum: 0,
      max_prize: 0,
      avg_prize: 0,
      meta: null,
    });
    renderRows();
  }

  function collectRowsFromTable() {
    const trs = linkedWinnersTbody.querySelectorAll('tr[data-idx]');
    trs.forEach(tr => {
      const idx = Number(tr.getAttribute('data-idx'));
      const it = rows[idx];

      it.display_name = tr.querySelector('[data-display]').value.trim();
      it.player_ref = tr.querySelector('[data-playerref]').value.trim() || null;
      it.country = (tr.querySelector('[data-country]').value.trim().toUpperCase() || null);

      it.rank = Number(tr.querySelector('[data-rank]').value || 1);
      it.position = Number(tr.querySelector('[data-position]').value || 0);

      it.wins_count = Number(tr.querySelector('[data-wins]').value || 0);
      it.prize_sum  = Number(tr.querySelector('[data-sum]').value || 0);
      it.max_prize  = Number(tr.querySelector('[data-max]').value || 0);
      it.avg_prize  = Number(tr.querySelector('[data-avg]').value || 0);

      const metaStr = tr.querySelector('[data-meta]').value.trim();
      it.meta = metaStr ? safeJson(metaStr) : null;
    });
  }

  async function syncWinners() {
    syncError.classList.add('d-none'); syncError.textContent = '';

    collectRowsFromTable();

    // validações mínimas do request
    if (rows.length < 1) return showSyncError('Inclua ao menos 1 winner.');
    for (const r of rows) {
      if (!r.display_name) return showSyncError('Todos os itens precisam de display_name.');
      if (!(r.rank >= 1)) return showSyncError('Rank deve ser >= 1.');
      if (r.country && r.country.length !== 2) return showSyncError('Country deve ter 2 letras (ex: BR).');
      if (r.meta === null && document.querySelector('[data-meta]')?.value?.trim()) {
        return showSyncError('Meta inválido (JSON malformado).');
      }
    }

    const items = rows
      .slice()
      .sort((a,b) => (a.rank ?? 1) - (b.rank ?? 1))
      .map((r, i) => ({
        player_ref: r.player_ref,
        display_name: r.display_name,
        country: r.country,
        rank: r.rank,
        position: r.position ?? i,
        wins_count: r.wins_count,
        prize_sum: r.prize_sum,
        max_prize: r.max_prize,
        avg_prize: r.avg_prize,
        meta: r.meta,
      }));

    const res = await apiFetch(`/api/v1/winners/batches/${managingBatchId}/results`, {
      method: 'PUT',
      body: JSON.stringify({ items })
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return showSyncError(body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')'));
    }

    toast('Winners sincronizados.');
    winnersModal.hide();
    await load(null);
  }

  function showSyncError(msg){
    syncError.textContent = msg;
    syncError.classList.remove('d-none');
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
    if (action === 'winners') openWinnersModal(id);
    if (action === 'publish') publishBatch(id);
    if (action === 'archive') archiveBatch(id);
    if (action === 'delete') destroyBatch(id);
  });

  document.getElementById('btnAddRow').addEventListener('click', addEmptyRow);
  document.getElementById('btnSortByRank').addEventListener('click', () => {
    collectRowsFromTable();
    rows.sort((a,b) => (a.rank ?? 1) - (b.rank ?? 1));
    renderRows();
  });

  linkedWinnersTbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-remove]');
    if (!btn) return;
    const tr = btn.closest('tr[data-idx]');
    const idx = Number(tr.getAttribute('data-idx'));
    rows.splice(idx, 1);
    renderRows();
  });

  document.getElementById('btnSyncWinners').addEventListener('click', syncWinners);

  load(null);
</script>
@endpush
@endsection