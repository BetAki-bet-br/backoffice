@extends('layouts.app')

@section('title', 'Footer - Backoffice')
@section('page-title', 'Footer')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Footer</h2>
    <div class="text-muted small">CRUD via API v1 • translations • links (sync) • publish</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Novo footer</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-5">
        <label class="form-label">Busca (key)</label>
        <input type="text" class="form-control" id="q" placeholder="Ex: main-footer">
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Status</label>
        <select class="form-select" id="status">
          <option value="">Todos</option>
          <option value="draft">draft</option>
          <option value="published">published</option>
          <option value="archived">archived</option>
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Country</label>
        <input class="form-control text-uppercase" id="country" placeholder="BR" maxlength="2">
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
          <th>Footer</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 120px;">Country</th>
          <th style="width: 160px;">Brand</th>
          <th style="width: 210px;">Publish at</th>
          <th style="width: 470px;" class="text-end">Ações</th>
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

{{-- Modal Create/Edit Footer --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTitle">Footer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-4">
            <label class="form-label">Key</label>
            <input class="form-control" id="f_key" placeholder="main-footer">
          </div>

          <div class="col-6 col-lg-4">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="draft">draft</option>
              <option value="published">published</option>
              <option value="archived">archived</option>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">Country</label>
            <input class="form-control text-uppercase" id="f_country" maxlength="2" placeholder="BR">
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">Brand</label>
            <input class="form-control" id="f_brand" placeholder="default">
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Publish at</label>
            <input type="datetime-local" class="form-control" id="f_publish_at">
          </div>

          <div class="col-12">
            <div class="alert alert-info small mb-0">
              <strong>Translations:</strong> adicione/edite abaixo (será enviado junto no save).<br>
              <strong>Links:</strong> use o botão <em>Links</em> na tabela depois de salvar.
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label mb-0">Translations</label>
              <button class="btn btn-sm btn-outline-primary" type="button" id="btnAddTranslation">Adicionar tradução</button>
            </div>

            <div class="table-soft mt-2">
              <div class="table-responsive">
                <table class="table mb-0 align-middle">
                  <thead>
                    <tr>
                      <th style="width: 110px;">Locale*</th>
                      <th style="width: 260px;">Legal title</th>
                      <th>Legal text</th>
                      <th>Disclaimer</th>
                      <th style="width: 90px;" class="text-end">Remover</th>
                    </tr>
                  </thead>
                  <tbody id="translationsTbody">
                    <tr><td colspan="5" class="text-muted p-3">Nenhuma tradução (opcional).</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="form-text">Locale exemplo: pt-BR, en, es.</div>
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

{{-- Modal Sync Links --}}
<div class="modal fade" id="linksModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xxl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Links do Footer</h5>
          <div class="text-muted small" id="linksSubtitle">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
          <div class="text-muted small">Campos: block, label, url, icon, target, position, is_active.</div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnSortByPosition">Ordenar por position</button>
            <button class="btn btn-sm btn-outline-primary" type="button" id="btnAddLink">Adicionar link</button>
          </div>
        </div>

        <div class="table-soft">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead>
                <tr>
                  <th style="width: 90px;">ID</th>
                  <th style="width: 160px;">Block</th>
                  <th style="width: 260px;">Label</th>
                  <th>URL</th>
                  <th style="width: 180px;">Icon</th>
                  <th style="width: 120px;">Target</th>
                  <th style="width: 110px;">Position</th>
                  <th style="width: 110px;">Active</th>
                  <th style="width: 90px;" class="text-end">Remover</th>
                </tr>
              </thead>
              <tbody id="linksTbody">
                <tr><td colspan="9" class="text-muted p-4">Carregando…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="alert alert-danger d-none mt-3 small" id="syncError"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
        <button class="btn btn-primary" id="btnSyncLinks">Salvar links</button>
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

  const linksModal = new bootstrap.Modal(document.getElementById('linksModal'));
  const linksSubtitle = document.getElementById('linksSubtitle');
  const linksTbody = document.getElementById('linksTbody');
  const syncError = document.getElementById('syncError');

  const translationsTbody = document.getElementById('translationsTbody');

  let nextCursor = null;
  let prevCursor = null;

  let editingId = null;
  let managingFooterId = null;

  const translations = []; // [{locale, legal_title, legal_text, disclaimer}]
  const links = []; // [{id?, block,label,url,icon,target,position,is_active}]

  function badge(status) {
    const s = (status || '').toLowerCase();
    const map = {
      published: 'bg-success-subtle text-success',
      draft: 'bg-warning-subtle text-warning',
      archived: 'bg-secondary-subtle text-secondary',
    };
    const cls = map[s] || 'bg-light text-muted';
    return `<span class="badge badge-status ${cls}">${status || '—'}</span>`;
  }

  function fmtDate(dt) {
    if (!dt) return '—';
    try { return new Date(dt).toLocaleString('pt-BR'); } catch { return dt; }
  }

  function isoToInput(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function inputToIso(v) {
    if (!v) return null;
    const d = new Date(v);
    return d.toISOString();
  }

  function getFilters() {
    return {
      q: document.getElementById('q').value.trim(),
      status: document.getElementById('status').value.trim(),
      country: document.getElementById('country').value.trim().toUpperCase(),
    };
  }

  async function load(cursor = null) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Carregando…</td></tr>`;

    const params = new URLSearchParams();
    if (cursor) params.set('cursor', cursor);

    const { q, status, country } = getFilters();
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    if (country) params.set('country', country);

    const res = await apiFetch('/api/v1/footers?' + params.toString());
    if (!res.ok) {
      toast('Falha ao carregar footers (' + res.status + ')', 'danger');
      tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-4">Erro ao carregar.</td></tr>`;
      return;
    }

    const data = await res.json();
    const rows = data.data || [];

    nextCursor = data.next_cursor || null;
    prevCursor = data.prev_cursor || null;

    info.textContent = `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(f => `
      <tr>
        <td class="text-muted">#${f.id}</td>
        <td>
          <div class="fw-semibold">${f.key || '—'}</div>
          <div class="text-muted small">${(f.translations?.length ?? 0)} translations • ${(f.links?.length ?? 0)} links</div>
        </td>
        <td>${badge(f.status)}</td>
        <td class="text-muted">${f.country || '—'}</td>
        <td class="text-muted">${f.brand || '—'}</td>
        <td class="text-muted small">${fmtDate(f.publish_at)}</td>
        <td class="text-end">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${f.id}">Editar</button>
            <button class="btn btn-sm btn-outline-primary" data-action="links" data-id="${f.id}">Links</button>
            <button class="btn btn-sm btn-outline-success" data-action="publish" data-id="${f.id}">Publicar</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${f.id}">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function renderTranslations() {
    if (!translations.length) {
      translationsTbody.innerHTML = `<tr><td colspan="5" class="text-muted p-3">Nenhuma tradução (opcional).</td></tr>`;
      return;
    }

    translationsTbody.innerHTML = translations.map((t, idx) => `
      <tr data-t-idx="${idx}">
        <td><input class="form-control form-control-sm" data-locale value="${t.locale ?? ''}" placeholder="pt-BR"></td>
        <td><input class="form-control form-control-sm" data-legal-title value="${t.legal_title ?? ''}"></td>
        <td><textarea class="form-control form-control-sm" rows="2" data-legal-text>${t.legal_text ?? ''}</textarea></td>
        <td><textarea class="form-control form-control-sm" rows="2" data-disclaimer>${t.disclaimer ?? ''}</textarea></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" type="button" data-remove-translation>Remover</button>
        </td>
      </tr>
    `).join('');
  }

  function collectTranslationsFromUI() {
    translationsTbody.querySelectorAll('tr[data-t-idx]').forEach(tr => {
      const idx = Number(tr.getAttribute('data-t-idx'));
      translations[idx].locale = tr.querySelector('[data-locale]').value.trim();
      translations[idx].legal_title = tr.querySelector('[data-legal-title]').value.trim() || null;
      translations[idx].legal_text = tr.querySelector('[data-legal-text]').value.trim() || null;
      translations[idx].disclaimer = tr.querySelector('[data-disclaimer]').value.trim() || null;
    });
  }

  function fillFooterForm(item) {
    document.getElementById('f_key').value = item?.key || '';
    document.getElementById('f_status').value = item?.status || 'draft';
    document.getElementById('f_country').value = (item?.country || '').toUpperCase();
    document.getElementById('f_brand').value = item?.brand || '';
    document.getElementById('f_publish_at').value = isoToInput(item?.publish_at);

    translations.length = 0;
    (item?.translations || []).forEach(t => translations.push({
      locale: t.locale, legal_title: t.legal_title, legal_text: t.legal_text, disclaimer: t.disclaimer
    }));
    renderTranslations();
  }

  function buildFooterPayload() {
    collectTranslationsFromUI();

    return {
      key: document.getElementById('f_key').value.trim(),
      status: document.getElementById('f_status').value,
      country: (document.getElementById('f_country').value.trim().toUpperCase() || null),
      brand: (document.getElementById('f_brand').value.trim() || null),
      publish_at: inputToIso(document.getElementById('f_publish_at').value),
      translations: translations
        .filter(t => t.locale && t.locale.trim().length)
        .map(t => ({
          locale: t.locale.trim(),
          legal_title: t.legal_title || null,
          legal_text: t.legal_text || null,
          disclaimer: t.disclaimer || null,
        })),
    };
  }

  function showSaveError(msg) {
    saveError.textContent = msg;
    saveError.classList.remove('d-none');
  }

  function openNew() {
    editingId = null;
    editTitle.textContent = 'Novo footer';
    saveError.classList.add('d-none'); saveError.textContent = '';
    fillFooterForm({ key:'', status:'draft', country:null, brand:'default', publish_at:null, translations:[] });
    editModal.show();
  }

  async function openEdit(id) {
    editingId = String(id);
    editTitle.textContent = 'Editar footer #' + id;
    saveError.classList.add('d-none'); saveError.textContent = '';

    const res = await apiFetch('/api/v1/footers/' + id);
    if (!res.ok) return toast('Falha ao buscar footer (' + res.status + ')', 'danger');

    const item = await res.json();
    fillFooterForm(item);
    editModal.show();
  }

  async function saveFooter() {
    saveError.classList.add('d-none'); saveError.textContent = '';

    const payload = buildFooterPayload();
    const allowedStatus = ['draft','published','archived'];

    if (!payload.key) return showSaveError('Key é obrigatório.');
    if (!allowedStatus.includes(payload.status)) return showSaveError('Status inválido.');
    if (payload.country && payload.country.length !== 2) return showSaveError('Country deve ter 2 letras (ex: BR).');

    // valida translations
    for (const t of payload.translations || []) {
      if (!t.locale) return showSaveError('Locale é obrigatório nas translations.');
    }

    const isEdit = !!editingId;
    const url = isEdit ? ('/api/v1/footers/' + editingId) : '/api/v1/footers';
    const method = isEdit ? 'PUT' : 'POST';

    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return showSaveError(body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')'));
    }

    toast(isEdit ? 'Footer atualizado.' : 'Footer criado.');
    editModal.hide();
    await load(null);
  }

  async function destroyFooter(id) {
    if (!confirm('Excluir footer #' + id + '?')) return;
    const res = await apiFetch('/api/v1/footers/' + id, { method: 'DELETE' });
    if (!res.ok) return toast('Falha ao excluir (' + res.status + ')', 'danger');
    toast('Footer excluído.');
    await load(null);
  }

  async function publishFooter(id) {
    const res = await apiFetch('/api/v1/footers/' + id + '/publish', { method: 'POST' });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return toast('Falha ao publicar: ' + (body ? JSON.stringify(body) : res.status), 'danger');
    }
    toast('Footer publicado.');
    await load(null);
  }

  // ===== Links sync =====
  function renderLinks() {
    if (!links.length) {
      linksTbody.innerHTML = `<tr><td colspan="9" class="text-muted p-4">Nenhum link.</td></tr>`;
      return;
    }

    linksTbody.innerHTML = links.map((l, idx) => `
      <tr data-l-idx="${idx}">
        <td class="text-muted">
          <input class="form-control form-control-sm" data-id value="${l.id ?? ''}" placeholder="(novo)" readonly>
        </td>
        <td><input class="form-control form-control-sm" data-block value="${l.block ?? ''}" placeholder="help"></td>
        <td><input class="form-control form-control-sm" data-label value="${l.label ?? ''}" placeholder="Ajuda"></td>
        <td><input class="form-control form-control-sm" data-url value="${l.url ?? ''}" placeholder="https://..."></td>
        <td><input class="form-control form-control-sm" data-icon value="${l.icon ?? ''}" placeholder="bi bi-instagram"></td>
        <td>
          <select class="form-select form-select-sm" data-target>
            <option value="">(none)</option>
            <option value="_self" ${l.target==='_self'?'selected':''}>_self</option>
            <option value="_blank" ${l.target==='_blank'?'selected':''}>_blank</option>
          </select>
        </td>
        <td><input type="number" min="0" class="form-control form-control-sm" data-position value="${l.position ?? idx}"></td>
        <td class="text-center">
          <input type="checkbox" class="form-check-input" data-active ${l.is_active ? 'checked' : ''}>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" type="button" data-remove-link>Remover</button>
        </td>
      </tr>
    `).join('');
  }

  function collectLinksFromUI() {
    linksTbody.querySelectorAll('tr[data-l-idx]').forEach(tr => {
      const idx = Number(tr.getAttribute('data-l-idx'));
      const it = links[idx];

      const idVal = tr.querySelector('[data-id]').value.trim();
      it.id = idVal ? Number(idVal) : null;

      it.block = tr.querySelector('[data-block]').value.trim() || null;
      it.label = tr.querySelector('[data-label]').value.trim() || null;
      it.url = tr.querySelector('[data-url]').value.trim() || null;
      it.icon = tr.querySelector('[data-icon]').value.trim() || null;
      it.target = tr.querySelector('[data-target]').value || null;
      it.position = Number(tr.querySelector('[data-position]').value || idx);
      it.is_active = tr.querySelector('[data-active]').checked;
    });
  }

  async function openLinksModal(id) {
    managingFooterId = String(id);
    syncError.classList.add('d-none'); syncError.textContent = '';
    links.length = 0;

    linksTbody.innerHTML = `<tr><td colspan="9" class="text-muted p-4">Carregando…</td></tr>`;

    const res = await apiFetch('/api/v1/footers/' + id);
    if (!res.ok) return toast('Falha ao carregar footer (' + res.status + ')', 'danger');

    const footer = await res.json();
    linksSubtitle.textContent = `${footer.key} • ${footer.country || '—'} • ${footer.status}`;

    (footer.links || []).forEach(l => links.push({
      id: l.id,
      block: l.block,
      label: l.label,
      url: l.url,
      icon: l.icon,
      target: l.target,
      position: l.position,
      is_active: !!l.is_active,
    }));

    renderLinks();
    linksModal.show();
  }

  function addEmptyLink() {
    links.push({ id:null, block:null, label:null, url:null, icon:null, target:null, position: links.length, is_active:true });
    renderLinks();
  }

  function showSyncError(msg) {
    syncError.textContent = msg;
    syncError.classList.remove('d-none');
  }

  async function syncLinks() {
    syncError.classList.add('d-none'); syncError.textContent = '';
    collectLinksFromUI();

    const payload = {
      links: links.map((l, idx) => ({
        id: l.id || undefined,
        block: l.block,
        label: l.label,
        url: l.url,
        icon: l.icon,
        target: l.target,
        position: l.position ?? idx,
        is_active: !!l.is_active,
      }))
    };

    // valida mínima
    for (const l of payload.links) {
      if (!l.label || !l.url) return showSyncError('Cada link precisa de label e url.');
    }

    const res = await apiFetch(`/api/v1/footers/${managingFooterId}/links`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return showSyncError(body ? JSON.stringify(body) : ('Erro ao salvar links (' + res.status + ')'));
    }

    toast('Links sincronizados.');
    linksModal.hide();
    await load(null);
  }

  // ===== Events =====
  document.getElementById('btnNew').addEventListener('click', openNew);
  document.getElementById('btnReload').addEventListener('click', () => load(null));
  document.getElementById('btnSearch').addEventListener('click', () => load(null));
  document.getElementById('btnClear').addEventListener('click', () => {
    document.getElementById('q').value = '';
    document.getElementById('status').value = '';
    document.getElementById('country').value = '';
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

  document.getElementById('btnSave').addEventListener('click', saveFooter);

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');

    if (action === 'edit') openEdit(id);
    if (action === 'links') openLinksModal(id);
    if (action === 'publish') publishFooter(id);
    if (action === 'delete') destroyFooter(id);
  });

  document.getElementById('btnAddTranslation').addEventListener('click', () => {
    translations.push({ locale:'pt-BR', legal_title:null, legal_text:null, disclaimer:null });
    renderTranslations();
  });

  translationsTbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-remove-translation]');
    if (!btn) return;
    const tr = btn.closest('tr[data-t-idx]');
    const idx = Number(tr.getAttribute('data-t-idx'));
    translations.splice(idx, 1);
    renderTranslations();
  });

  document.getElementById('btnAddLink').addEventListener('click', addEmptyLink);

  linksTbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-remove-link]');
    if (!btn) return;
    const tr = btn.closest('tr[data-l-idx]');
    const idx = Number(tr.getAttribute('data-l-idx'));
    links.splice(idx, 1);
    renderLinks();
  });

  document.getElementById('btnSortByPosition').addEventListener('click', () => {
    collectLinksFromUI();
    links.sort((a,b) => (a.position ?? 0) - (b.position ?? 0));
    renderLinks();
  });

  document.getElementById('btnSyncLinks').addEventListener('click', syncLinks);

  load(null);
</script>
@endpush
@endsection