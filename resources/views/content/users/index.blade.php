@extends('layouts.app')

@section('title', 'Usuários - Backoffice')
@section('page-title', 'Usuários')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Usuários</h2>
    <div class="text-muted small">CRUD via API v1 • roles (Spatie) • status</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Novo usuário</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-7">
        <label class="form-label">Busca (name/email)</label>
        <input type="text" class="form-control" id="q" placeholder="Ex: admin@betaki.com ou Luiz">
      </div>

      <div class="col-12 col-lg-5 d-flex gap-2">
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
          <th>Usuário</th>
          <th style="width: 150px;">Status</th>
          <th>Roles</th>
          <th style="width: 320px;" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="5" class="text-muted p-4">Carregando…</td></tr>
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
        <h5 class="modal-title" id="editTitle">Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <label class="form-label">Nome</label>
            <input class="form-control" id="f_name" placeholder="Nome do usuário">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Email</label>
            <input class="form-control" id="f_email" type="email" placeholder="email@dominio.com">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Senha</label>
            <input class="form-control" id="f_password" type="password" placeholder="(obrigatória ao criar; opcional ao editar)">
            <div class="form-text">Ao editar: deixe em branco para não alterar.</div>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="active">active</option>
              <option value="suspended">suspended</option>
              <option value="disabled">disabled</option>
            </select>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label mb-0">Roles</label>
              <button class="btn btn-sm btn-outline-secondary" type="button" id="btnReloadRoles">Recarregar roles</button>
            </div>
            <div class="mt-2" id="rolesBox">
              <div class="text-muted small">Carregando roles…</div>
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

{{-- Toast container --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

@push('scripts')
<script>
  // ✅ NÃO declare TOKEN_KEY aqui.
  // Vamos assumir que o layout já tem getToken()/setToken()/clearToken().

  function toast(message, variant = 'success') {
    const container = document.querySelector('.toast-container') || (() => {
      const el = document.createElement('div');
      el.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      el.style.zIndex = 1080;
      document.body.appendChild(el);
      return el;
    })();

    const el = document.createElement('div');
    const cls = (variant === 'danger' ? 'danger' : (variant === 'warning' ? 'warning' : (variant === 'secondary' ? 'secondary' : 'success')));
    el.className = 'toast align-items-center text-bg-' + cls + ' border-0';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    `;
    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 2600 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  async function apiFetch(url, options = {}) {
    const token = (typeof getToken === 'function') ? getToken() : null;

    const headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
    }, options.headers || {});

    return fetch(url, { ...options, headers });
  }

  function badgeStatus(status) {
    const s = (status || '').toLowerCase();
    const map = {
      active: 'bg-success-subtle text-success',
      suspended: 'bg-warning-subtle text-warning',
      disabled: 'bg-secondary-subtle text-secondary',
    };
    const cls = map[s] || 'bg-light text-muted';
    return `<span class="badge badge-status ${cls}">${status || '—'}</span>`;
  }

  // ===== State =====
  const tbody = document.getElementById('tbody');
  const info = document.getElementById('paginationInfo');

  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const editTitle = document.getElementById('editTitle');
  const saveError = document.getElementById('saveError');

  let nextCursor = null;
  let prevCursor = null;
  let editingId = null;

  let rolesCache = [];
  const rolesBox = document.getElementById('rolesBox');

  function getSelectedRoles() {
    return Array.from(rolesBox.querySelectorAll('input[type="checkbox"][data-role]:checked'))
      .map(i => i.value);
  }

  function setSelectedRoles(names = []) {
    rolesBox.querySelectorAll('input[type="checkbox"][data-role]').forEach(i => {
      i.checked = names.includes(i.value);
    });
  }

  function renderRoles() {
    if (!rolesCache.length) {
      rolesBox.innerHTML = `<div class="text-muted small">Nenhuma role encontrada.</div>`;
      return;
    }

    rolesBox.innerHTML = `
      <div class="row g-2">
        ${rolesCache.map(r => `
          <div class="col-12 col-md-6">
            <label class="form-check">
              <input class="form-check-input" type="checkbox" data-role value="${r.name}">
              <span class="form-check-label">${r.name}</span>
            </label>
          </div>
        `).join('')}
      </div>
    `;
  }

  async function loadRoles() {
    rolesBox.innerHTML = `<div class="text-muted small">Carregando roles…</div>`;

    const res = await apiFetch('/api/v1/roles');
    if (!res.ok) {
      rolesBox.innerHTML = `<div class="text-danger small">Erro ao carregar roles (${res.status}).</div>`;
      return;
    }

    const data = await res.json();
    const list = data.data || data || [];
    rolesCache = (Array.isArray(list) ? list : []).map(r => ({ name: r.name }));
    renderRoles();
  }

  function getFilters() {
    return { q: document.getElementById('q').value.trim() };
  }

  async function loadUsers(cursor = null) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Carregando…</td></tr>`;

    const params = new URLSearchParams();
    if (cursor) params.set('cursor', cursor);

    const { q } = getFilters();
    if (q) params.set('q', q);

    const res = await apiFetch('/api/v1/users?' + params.toString());
    if (!res.ok) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-danger p-4">Erro ao carregar (${res.status}).</td></tr>`;
      return;
    }

    const data = await res.json();
    const rows = data.data || [];

    nextCursor = data.next_cursor || null;
    prevCursor = data.prev_cursor || null;

    info.textContent = `Carregados: ${rows.length} • Próximo: ${nextCursor ? 'sim' : 'não'} • Anterior: ${prevCursor ? 'sim' : 'não'}`;

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Nenhum registro.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(u => {
      const roles = (u.roles_list || u.roles?.map(r => r.name) || []);
      return `
        <tr>
          <td class="text-muted">#${u.id}</td>
          <td>
            <div class="fw-semibold">${u.name || '—'}</div>
            <div class="text-muted small">${u.email || '—'}</div>
          </td>
          <td>${badgeStatus(u.status)}</td>
          <td class="text-muted small">${roles.length ? roles.join(', ') : '—'}</td>
          <td class="text-end">
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${u.id}">Editar</button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${u.id}">Excluir</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function fillForm(user) {
    document.getElementById('f_name').value = user?.name || '';
    document.getElementById('f_email').value = user?.email || '';
    document.getElementById('f_password').value = '';
    document.getElementById('f_status').value = user?.status || 'active';

    const roles = user?.roles_list || user?.roles?.map(r => r.name) || [];
    setSelectedRoles(roles);
  }

  function showSaveError(msg) {
    saveError.textContent = msg;
    saveError.classList.remove('d-none');
  }

  function openNew() {
    editingId = null;
    editTitle.textContent = 'Novo usuário';
    saveError.classList.add('d-none'); saveError.textContent = '';
    fillForm({ name:'', email:'', status:'active', roles_list:[] });
    editModal.show();
  }

  async function openEdit(id) {
    editingId = String(id);
    editTitle.textContent = 'Editar usuário #' + id;
    saveError.classList.add('d-none'); saveError.textContent = '';

    const res = await apiFetch('/api/v1/users/' + id);
    if (!res.ok) return toast('Falha ao buscar usuário (' + res.status + ')', 'danger');

    const user = await res.json();
    fillForm(user);
    editModal.show();
  }

  async function saveUser() {
    saveError.classList.add('d-none'); saveError.textContent = '';

    const payload = {
      name: document.getElementById('f_name').value.trim(),
      email: document.getElementById('f_email').value.trim(),
      status: document.getElementById('f_status').value,
      roles: getSelectedRoles(),
    };

    const pass = document.getElementById('f_password').value;
    if (!editingId) {
      if (!pass) return showSaveError('Senha é obrigatória ao criar.');
      payload.password = pass;
    } else if (pass) {
      payload.password = pass;
    }

    if (!payload.name) return showSaveError('Nome é obrigatório.');
    if (!payload.email) return showSaveError('Email é obrigatório.');

    const allowedStatus = ['active','suspended','disabled'];
    if (!allowedStatus.includes(payload.status)) return showSaveError('Status inválido.');

    const url = editingId ? ('/api/v1/users/' + editingId) : '/api/v1/users';
    const method = editingId ? 'PUT' : 'POST';

    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return showSaveError(body ? JSON.stringify(body) : ('Erro ao salvar (' + res.status + ')'));
    }

    toast(editingId ? 'Usuário atualizado.' : 'Usuário criado.');
    editModal.hide();
    await loadUsers(null);
  }

  async function deleteUser(id) {
    if (!confirm('Excluir usuário #' + id + '?')) return;

    const res = await apiFetch('/api/v1/users/' + id, { method: 'DELETE' });

    if (res.status === 422) {
      const body = await res.json().catch(() => null);
      return toast(body?.error?.message || 'Não foi possível excluir.', 'warning');
    }

    if (!res.ok) return toast('Falha ao excluir (' + res.status + ')', 'danger');

    toast('Usuário excluído.');
    await loadUsers(null);
  }

  // ===== Wire =====
  document.getElementById('btnNew').addEventListener('click', openNew);
  document.getElementById('btnReload').addEventListener('click', () => loadUsers(null));
  document.getElementById('btnSearch').addEventListener('click', () => loadUsers(null));
  document.getElementById('btnClear').addEventListener('click', () => { document.getElementById('q').value=''; loadUsers(null); });
  document.getElementById('btnSave').addEventListener('click', saveUser);
  document.getElementById('btnReloadRoles').addEventListener('click', loadRoles);

  document.getElementById('prevBtn').addEventListener('click', () => {
    if (!prevCursor) return toast('Sem página anterior.', 'secondary');
    loadUsers(prevCursor);
  });
  document.getElementById('nextBtn').addEventListener('click', () => {
    if (!nextCursor) return toast('Sem próxima página.', 'secondary');
    loadUsers(nextCursor);
  });

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');
    if (action === 'edit') openEdit(id);
    if (action === 'delete') deleteUser(id);
  });

  async function init() {
    await loadRoles();
    await loadUsers(null);
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
@endpush
@endsection