@extends('layouts.app')

@section('title', 'Banners - Backoffice')
@section('page-title', 'Banners')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Banners</h2>
    <div class="text-muted small">Campos principais: slug, status, countries, publish_at/expire_at, link_url, utm_*, media</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Novo banner</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-3">
        <label class="form-label">Busca</label>
        <input type="text" class="form-control" id="q" placeholder="opcional">
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
        <label class="form-label">Vertical</label>
        <select class="form-select" id="vertical">
          <option value="">Todas</option>
          <option value="casino">Casino</option>
          <option value="live_casino">Cassino ao Vivo</option>
          <option value="sportbook">Sportbook</option>
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Country</label>
        <input type="text" class="form-control" id="countries" placeholder="Ex: BR,PT">
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
          <th>Slug</th>
          <th>Vertical</th>
          <th>Countries</th>
          <th>Agendamento</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 260px;" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="6" class="text-muted p-4">Carregando…</td></tr>
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
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTitle">Banner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-4">
            <label class="form-label">Slug</label>
            <input class="form-control" id="f_slug" placeholder="ex: banner-natal">
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">Vertical</label>
            <select class="form-select" id="f_vertical">
                <option value="casino">Casino</option>
                <option value="live_casino">Cassino ao Vivo</option>
                <option value="sportbook">Sportbook</option>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="draft">draft</option>
              <option value="published">published</option>
              <option value="archived">archived</option>
            </select>
          </div>

          <div class="col-6 col-lg-4">
            <label class="form-label">Countries (CSV)</label>
            <input class="form-control" id="f_countries" placeholder="BR,PT">
            <div class="form-text">Será enviado como array: ["BR","PT"]</div>
          </div>

          <div class="col-12 col-lg-3">
            <label class="form-label">Link URL</label>
            <input class="form-control" id="f_link_url" placeholder="https://... ou /rota">
          </div>

          <div class="col-12 col-lg-3">
            <label class="form-label">Publish at</label>
            <input type="datetime-local" class="form-control" id="f_publish_at">
          </div>

          <div class="col-12 col-lg-3">
            <label class="form-label">Expire at</label>
            <input type="datetime-local" class="form-control" id="f_expire_at">
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">UTM Source</label>
            <input class="form-control" id="f_utm_source">
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">UTM Medium</label>
            <input class="form-control" id="f_utm_medium">
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">UTM Campaign</label>
            <input class="form-control" id="f_utm_campaign">
          </div>

          <div class="col-12">
            <x-file-upload name="cover_url" label="Imagem (Cover)" />
          </div>

          <div class="col-12">
            <label class="form-label">Media (JSON)</label>
            <textarea class="form-control font-monospace" rows="6" id="f_media" spellcheck="false">{}</textarea>
            <div class="form-text">Seu model usa cast array. Ex.: {"desktop":"...","mobile":"..."}</div>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label mb-0">Translations</label>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="addTranslation">Adicionar locale</button>
            </div>

            <div id="translationsWrap" class="d-grid gap-2"></div>
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

  // cursor paginate state
  let nextCursor = null;
  let prevCursor = null;

  // cache items by id (para editar sem endpoint show)
  const cacheById = new Map();

  let editingId = null;

  function badge(statusOrVertical) {
    const s = (statusOrVertical || '').toLowerCase();
    const map = {
      published: 'bg-success-subtle text-success',
      review: 'bg-info-subtle text-info',
      draft: 'bg-warning-subtle text-warning',
      archived: 'bg-secondary-subtle text-secondary',
      casino: 'bg-info-subtle text-info-emphasis',
      live_casino: 'bg-warning-subtle text-warning-emphasis',
      sportbook: 'bg-primary-subtle text-primary',
    };
    const cls = map[s] || 'bg-light text-muted';
    return `<span class="badge badge-status ${cls}">${statusOrVertical || '—'}</span>`;
  }

  function fmtDate(dt) {
    if (!dt) return '—';
    // dt vem como ISO
    try {
      const d = new Date(dt);
      return d.toLocaleString('pt-BR');
    } catch { return dt; }
  }

  function render(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted p-4">Nenhum registro.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(item => {
      const countries = Array.isArray(item.countries) ? item.countries.join(', ') : '—';
      const sched = `${fmtDate(item.publish_at)} → ${fmtDate(item.expire_at)}`;
      return `
        <tr>
          <td class="text-muted">#${item.id}</td>
          <td>
            <div class="fw-semibold">${item.slug || '—'}</div>
            <div class="text-muted small">${item.link_url || ''}</div>
          </td>
          <td>${badge(item.vertical || '—')}</td>
          <td class="text-muted">${countries}</td>
          <td class="text-muted small">${sched}</td>
          <td>${badge(item.status)}</td>
          <td class="text-end">
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-primary" data-action="copy" data-id="${item.id}">Copiar link</button>
              <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${item.id}">Editar</button>
              <button class="btn btn-sm btn-outline-success" data-action="publish" data-id="${item.id}">Publicar</button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">Excluir</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function csvToArray(s) {
    const parts = (s || '').split(',').map(x => x.trim()).filter(Boolean);
    return parts.length ? parts : [];
  }

  function isoToInput(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    const pad = n => String(n).padStart(2, '0');
    // datetime-local: YYYY-MM-DDTHH:mm
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function inputToIso(v) {
    // datetime-local → ISO string (mantém timezone local)
    if (!v) return null;
    const d = new Date(v);
    return d.toISOString();
  }

  function addTranslationBlock(initial = { locale: 'pt-BR', data: {} }) {
    const wrap = document.getElementById('translationsWrap');

    const id = 't_' + Math.random().toString(16).slice(2);
    const el = document.createElement('div');
    el.className = 'card card-soft';
    el.innerHTML = `
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0">Locale</label>
            <input class="form-control form-control-sm" style="max-width: 120px" data-locale value="${initial.locale || ''}">
          </div>
          <button class="btn btn-sm btn-outline-danger" type="button" data-remove>Remover</button>
        </div>

        <label class="form-label">Dados (JSON)</label>
        <textarea class="form-control font-monospace" rows="5" data-json spellcheck="false">${JSON.stringify(initial.data || {}, null, 2)}</textarea>
        <div class="form-text">Será enviado como item do array translations, com { locale, ...campos }</div>
      </div>
    `;

    el.querySelector('[data-remove]').addEventListener('click', () => el.remove());
    wrap.appendChild(el);
  }

  function clearTranslations() {
    document.getElementById('translationsWrap').innerHTML = '';
  }

  function readTranslations() {
    const items = [];
    document.querySelectorAll('#translationsWrap .card').forEach(card => {
      const locale = card.querySelector('[data-locale]').value.trim();
      const jsonText = card.querySelector('[data-json]').value;
      if (!locale) return;

      let data = {};
      try { data = JSON.parse(jsonText || '{}'); } catch { data = {}; }

      // Garantir que media é sempre um array (pode estar vazio)
      if (!data.media) {
        data.media = {};
      }

      items.push({ locale, ...data });
    });
    return items;
  }

  function fillForm(item) {
    document.getElementById('f_slug').value = item.slug || '';
    document.getElementById('f_vertical').value = item.vertical || 'casino';
    document.getElementById('f_status').value = item.status || 'draft';
    document.getElementById('f_countries').value = Array.isArray(item.countries) ? item.countries.join(',') : '';
    document.getElementById('f_link_url').value = item.link_url || '';
    document.getElementById('f_publish_at').value = isoToInput(item.publish_at);
    document.getElementById('f_expire_at').value = isoToInput(item.expire_at);
    document.getElementById('f_utm_source').value = item.utm_source || '';
    document.getElementById('f_utm_medium').value = item.utm_medium || '';
    document.getElementById('f_utm_campaign').value = item.utm_campaign || '';

    // Atualizar hidden field de cover_url (se existir)
    const coverUrlField = document.getElementById('f_cover_url');
    if (coverUrlField) {
      coverUrlField.value = item?.cover_url || '';
    }

    // Atualizar imagem atual no componente
    const currentImageContainer = document.querySelector('.current-image');
    const currentImageElement = document.getElementById('current-cover_url-image');
    
    if (item?.cover_url) {
      if (!currentImageContainer) {
        // Se não existe, criar o container
        const fileInputContainer = document.querySelector('.file-upload-container');
        const container = document.createElement('div');
        container.className = 'current-image mb-2';
        container.innerHTML = `
          <img id="current-cover_url-image" 
               src="${item.cover_url}" 
               alt="Current Imagem"
               class="img-thumbnail"
               style="max-width: 200px; max-height: 200px;">
          <button type="button" 
                  class="btn btn-sm btn-danger ms-2"
                  onclick="removeCurrentCoverUrl()">
            Remover Atual
          </button>
        `;
        fileInputContainer.insertBefore(container, fileInputContainer.firstChild);
      } else {
        // Se existe, atualizar a imagem
        currentImageElement.src = item.cover_url;
        currentImageContainer.style.display = 'block';
      }
    } else if (currentImageContainer) {
      // Se não há imagem, esconder o container
      currentImageContainer.style.display = 'none';
    }

    // Reset file input
    const fileInput = document.getElementById('cover_url');
    if (fileInput) {
      fileInput.value = '';
    }

    document.getElementById('f_media').value = JSON.stringify(item.media || {}, null, 2);

    clearTranslations();
    (item.translations || []).forEach(t => {
      const { locale, id, banner_id, created_at, updated_at, ...rest } = t;
      addTranslationBlock({ locale, data: rest });
    });

    if (!(item.translations || []).length) {
      addTranslationBlock({ locale: 'pt-BR', data: {} });
    }
  }

  function buildPayload() {
    const mediaText = document.getElementById('f_media').value;
    let media = {};
    try { media = JSON.parse(mediaText || '{}'); } catch { media = {}; }

    const fileInput = document.getElementById('cover_url');
    const hasFile = fileInput && fileInput.files && fileInput.files[0];

    const payload = {
      slug: document.getElementById('f_slug').value.trim() || null,
      status: document.getElementById('f_status').value,
      countries: csvToArray(document.getElementById('f_countries').value),
      link_url: document.getElementById('f_link_url').value.trim() || null,
      publish_at: inputToIso(document.getElementById('f_publish_at').value),
      expire_at: inputToIso(document.getElementById('f_expire_at').value),

      utm_source: document.getElementById('f_utm_source').value.trim() || null,
      utm_medium: document.getElementById('f_utm_medium').value.trim() || null,
      utm_campaign: document.getElementById('f_utm_campaign').value.trim() || null,

      media,
      translations: readTranslations(),
    };

    // Só incluir cover_url se há novo arquivo
    // Se não há arquivo novo, não enviamos o campo (para não validar como nulo)
    if (hasFile) {
      payload.cover_url = null; // FormData será adicionado separadamente
    } else {
      // Sem novo arquivo - manter a URL atual (do hidden field)
      const coverUrlField = document.getElementById('f_cover_url');
      const coverUrl = coverUrlField ? (coverUrlField.value.trim() || null) : null;
      if (coverUrl) {
        payload.cover_url = coverUrl;
      }
    }

    return payload;
  }

  async function load(cursor = null, direction = 'initial') {
    tbody.innerHTML = `<tr><td colspan="6" class="text-muted p-4">Carregando…</td></tr>`;

    const params = new URLSearchParams();
    if (cursor) params.set('cursor', cursor);

    const q = document.getElementById('q').value.trim();
    if (q) params.set('q', q);

    const status = document.getElementById('status').value;
    if (status) params.set('status', status);

    const countries = document.getElementById('countries').value.trim();
    if (countries) params.set('countries', countries);

    const vertical = document.getElementById('vertical').value;
    if (vertical) params.set('vertical', vertical);

    const res = await apiFetch('/api/v1/banners?' + params.toString());
    if (!res.ok) {
      toast('Falha ao carregar banners (' + res.status + ')', 'danger');
      tbody.innerHTML = `<tr><td colspan="6" class="text-danger p-4">Erro ao carregar.</td></tr>`;
      return;
    }

    const data = await res.json();

    const rows = data.data || [];
    cacheById.clear();
    rows.forEach(r => cacheById.set(String(r.id), r));

    // cursor paginate retorna links/next_cursor/prev_cursor
    nextCursor = data.next_cursor || null;
    prevCursor = data.prev_cursor || null;

    info.textContent = `Carregados: ${rows.length} • Próximo cursor: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;

    render(rows);
  }

  function openNew() {
    editingId = null;
    saveError.classList.add('d-none');
    saveError.textContent = '';
    editTitle.textContent = 'Novo Banner';

    fillForm({
      slug: '',
      vertical: 'casino',
      status: 'draft',
      countries: ['BR'],
      publish_at: null,
      expire_at: null,
      link_url: '',
      utm_source: '',
      utm_medium: '',
      utm_campaign: '',
      media: {},
      translations: [{ locale: 'pt-BR' }]
    });

    modal.show();
  }

  function openEdit(id) {
    editingId = String(id);
    saveError.classList.add('d-none');
    saveError.textContent = '';
    editTitle.textContent = 'Editar Banner #' + id;

    const item = cacheById.get(String(id));
    if (!item) {
      toast('Item não encontrado na lista. (Faltando show na API?)', 'danger');
      return;
    }

    fillForm(item);
    modal.show();
  }

  function removeCurrentCoverUrl() {
    const currentImageContainer = document.querySelector('.current-image');
    if (currentImageContainer) {
      currentImageContainer.style.display = 'none';
    }
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
      
      // Adicionar arquivo
      formData.append('cover_url', fileInput.files[0]);
      
      // Adicionar TODOS os campos do payload
      Object.keys(payload).forEach(key => {
        const value = payload[key];
        
        if (value === null || value === undefined) {
          return;
        }
        
        if (Array.isArray(value)) {
          // Arrays: countries[0], countries[1], translations[0][...], etc
          if (key === 'countries') {
            value.forEach((item, i) => {
              formData.append(`${key}[${i}]`, item);
            });
          } else if (key === 'translations') {
            value.forEach((item, i) => {
              Object.keys(item).forEach(subkey => {
                const subvalue = item[subkey];
                if (typeof subvalue === 'object' && subvalue !== null) {
                  formData.append(`${key}[${i}][${subkey}]`, JSON.stringify(subvalue));
                } else {
                  formData.append(`${key}[${i}][${subkey}]`, subvalue);
                }
              });
            });
          }
        } else if (typeof value === 'object') {
          // Objetos: serializar como JSON
          const jsonValue = JSON.stringify(value);
          formData.append(key, jsonValue);
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
    const url = isEdit ? ('/api/v1/banners/' + editingId) : '/api/v1/banners';
    
    // Determinar método HTTP
    if (isEdit) {
      options.method = 'PUT';
    } else {
      options.method = 'POST';
    }
    if (!res.ok) {
      const resBody = await res.json().catch(() => null);
      saveError.textContent = resBody ? JSON.stringify(resBody) : ('Erro ao salvar (' + res.status + ')');
      saveError.classList.remove('d-none');
      return;
    }

    toast(isEdit ? 'Banner atualizado.' : 'Banner criado.');
    modal.hide();
    await load(null);
  }

  async function publish(id) {
    const res = await apiFetch('/api/v1/banners/' + id + '/publish', { method: 'POST' });
    if (!res.ok) {
      toast('Falha ao publicar (' + res.status + ')', 'danger');
      return;
    }
    toast('Banner publicado.');
    await load(null);
  }

  async function destroy(id) {
    if (!confirm('Excluir banner #' + id + '?')) return;

    const res = await apiFetch('/api/v1/banners/' + id, { method: 'DELETE' });
    if (!res.ok) {
      toast('Falha ao excluir (' + res.status + ') — sua API pode não ter destroy()', 'danger');
      return;
    }

    toast('Banner excluído.');
    await load(null);
  }

  async function copyLink(id) {
    const item = cacheById.get(String(id));
    if (!item) return toast('Banner nao encontrado.', 'danger');

    const media = item.media || {};
    const link = item.link_url || media.desktop || media.mobile || '';
    if (!link) return toast('Sem link para copiar.', 'secondary');

    try {
      await navigator.clipboard.writeText(link);
      toast('Link copiado.');
    } catch {
      const tmp = document.createElement('input');
      tmp.value = link;
      document.body.appendChild(tmp);
      tmp.select();
      document.execCommand('copy');
      tmp.remove();
      toast('Link copiado.');
    }
  }

  // UI events
  document.getElementById('btnNew').addEventListener('click', openNew);
  document.getElementById('btnReload').addEventListener('click', () => load(null));
  document.getElementById('btnSearch').addEventListener('click', () => load(null));
  document.getElementById('btnClear').addEventListener('click', () => {
    document.getElementById('q').value = '';
    document.getElementById('status').value = '';
    document.getElementById('countries').value = '';
    document.getElementById('vertical').value = '';
    load(null);
  });

  document.getElementById('prevBtn').addEventListener('click', () => {
    if (!prevCursor) return toast('Sem página anterior.', 'secondary');
    load(prevCursor, 'prev');
  });

  document.getElementById('nextBtn').addEventListener('click', () => {
    if (!nextCursor) return toast('Sem próxima página.', 'secondary');
    load(nextCursor, 'next');
  });

  document.getElementById('btnSave').addEventListener('click', save);
  document.getElementById('addTranslation').addEventListener('click', () => addTranslationBlock({ locale: 'pt-BR', data: {} }));

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');

    if (action === 'edit') openEdit(id);
    if (action === 'copy') copyLink(id);
    if (action === 'publish') publish(id);
    if (action === 'delete') destroy(id);
  });

  // initial load
  load(null);
</script>
@endpush
@endsection
