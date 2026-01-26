/**
 * Telegram Bots Management
 * Consome as rotas da API de Bots Telegram
 */

let currentPage = 1;
let currentFilters = {};
let editingBotId = null;
let deleteConfirmId = null;

// DOM Elements
const tbody = document.getElementById('tbody');
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

// Event Listeners
document.getElementById('btnNew').addEventListener('click', openCreateModal);
document.getElementById('btnReload').addEventListener('click', loadBots);
document.getElementById('btnSearch').addEventListener('click', searchBots);
document.getElementById('btnClear').addEventListener('click', clearFilters);
document.getElementById('btnSave').addEventListener('click', saveBotData);
document.getElementById('btnConfirmDelete').addEventListener('click', confirmDelete);
document.getElementById('prevBtn').addEventListener('click', () => previousPage());
document.getElementById('nextBtn').addEventListener('click', () => nextPage());

document.getElementById('q').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') searchBots();
});

// Load bots on page load
document.addEventListener('DOMContentLoaded', () => {
  loadBots();
});

/**
 * Load bots list
 */
async function loadBots() {
  try {
    const params = new URLSearchParams({
      page: currentPage,
      ...currentFilters,
    });

    const response = await apiFetch(`/api/v1/telegram-bots?${params}`);
    const data = await response.json();

    if (response.ok) {
      displayBots(data.data);
      updatePagination(data);
    } else {
      showError('Erro ao carregar bots');
      console.error(data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar bots');
  }
}

/**
 * Display bots in table
 */
function displayBots(bots) {
  if (!bots || bots.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted p-4">Nenhum bot encontrado</td></tr>';
    return;
  }

  tbody.innerHTML = bots.map(bot => `
    <tr>
      <td><code>${bot.id}</code></td>
      <td><strong>${bot.name}</strong></td>
      <td>@${bot.username}</td>
      <td>
        <span class="badge ${getStatusBadgeClass(bot.status)}">
          ${bot.status}
        </span>
      </td>
      <td>
        ${bot.webhook_set_at 
          ? `<span class="badge bg-success">✓ OK</span>` 
          : `<span class="badge bg-warning">✗ Não config.</span>`
        }
      </td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-info action-btn" onclick="viewDetails(${bot.id})">Detalhes</button>
        <button class="btn btn-sm btn-outline-primary action-btn" onclick="editBot(${bot.id})">Editar</button>
        <a href="/telegram-bots/${bot.id}/flows" class="btn btn-sm btn-outline-secondary action-btn">Fluxos</a>
        <a href="/telegram-bots/${bot.id}/statistics" class="btn btn-sm btn-outline-success action-btn">Stats</a>
        <button class="btn btn-sm btn-outline-danger action-btn" onclick="deleteBot(${bot.id})">Deletar</button>
      </td>
    </tr>
  `).join('');
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(status) {
  const classes = {
    'active': 'bg-success',
    'inactive': 'bg-secondary',
    'paused': 'bg-warning',
  };
  return classes[status] || 'bg-secondary';
}

/**
 * Open create modal
 */
function openCreateModal() {
  editingBotId = null;
  clearForm();
  document.getElementById('editTitle').textContent = 'Novo Bot Telegram';
  document.getElementById('tokenReadOnly').style.display = 'none';
  document.getElementById('f_bot_token').removeAttribute('readonly');
  document.getElementById('webhookSection').style.display = 'none';
  editModal.show();
}

/**
 * Edit bot
 */
async function editBot(botId) {
  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${botId}`);
    const data = await response.json();

    if (!response.ok) {
      showError('Erro ao carregar bot');
      return;
    }

    const bot = data.data;
    editingBotId = botId;

    // Populate form
    document.getElementById('f_name').value = bot.name || '';
    document.getElementById('f_username').value = bot.username || '';
    document.getElementById('f_bot_token').value = bot.bot_token || '';
    document.getElementById('f_status').value = bot.status || 'active';
    document.getElementById('f_description').value = bot.description || '';
    document.getElementById('f_api_url').value = bot.api_url || '';
    document.getElementById('f_api_key').value = bot.api_key || '';
    document.getElementById('f_portal_id').value = bot.portal_id || '';
    document.getElementById('f_group_chat_id').value = bot.group_chat_id || '';
    document.getElementById('f_group_invite_link').value = bot.group_invite_link || '';
    document.getElementById('f_register_url').value = bot.register_url || '';
    document.getElementById('f_debug_mode').checked = bot.debug_mode || false;

    document.getElementById('editTitle').textContent = 'Editar Bot Telegram';
    document.getElementById('tokenReadOnly').style.display = 'block';
    document.getElementById('f_bot_token').setAttribute('readonly', 'readonly');
    document.getElementById('webhookSection').style.display = 'block';

    // Setup webhook URL
    const webhookUrl = `${window.location.origin}/webhooks/telegram/${botId}`;
    document.getElementById('webhookUrl').textContent = webhookUrl;

    // Webhook action buttons
    document.getElementById('btnSetupWebhook').onclick = () => setupWebhook(botId);
    document.getElementById('btnTestWebhook').onclick = () => testWebhook(botId);
    document.getElementById('btnResetWebhook').onclick = () => resetWebhook(botId);

    editModal.show();
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar bot');
  }
}

/**
 * Save bot data
 */
async function saveBotData() {
  const formData = {
    name: document.getElementById('f_name').value,
    username: document.getElementById('f_username').value,
    status: document.getElementById('f_status').value,
    description: document.getElementById('f_description').value,
    api_url: document.getElementById('f_api_url').value,
    api_key: document.getElementById('f_api_key').value,
    portal_id: parseInt(document.getElementById('f_portal_id').value),
    group_chat_id: document.getElementById('f_group_chat_id').value || null,
    group_invite_link: document.getElementById('f_group_invite_link').value || null,
    register_url: document.getElementById('f_register_url').value || null,
    debug_mode: document.getElementById('f_debug_mode').checked,
  };

  // Add bot_token only if creating
  if (!editingBotId) {
    formData.bot_token = document.getElementById('f_bot_token').value;
  }

  try {
    const url = editingBotId 
      ? `/api/v1/telegram-bots/${editingBotId}`
      : '/api/v1/telegram-bots';
    
    const method = editingBotId ? 'PUT' : 'POST';

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (response.ok) {
      toast('Bot salvo com sucesso!');
      editModal.hide();
      loadBots();
    } else {
      showError('Erro ao salvar bot', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao salvar bot');
  }
}

/**
 * Setup webhook
 */
async function setupWebhook(botId) {
  try {
    const btn = document.getElementById('btnSetupWebhook');
    btn.disabled = true;
    btn.innerHTML = 'Configurando...';

    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/setup-webhook`, {
      method: 'POST',
    });

    const data = await response.json();

    if (response.ok) {
      toast('Webhook configurado com sucesso!');
      document.getElementById('webhookStatus').innerHTML = 
        '<div class="alert alert-success mb-0">✓ Webhook configurado no Telegram</div>';
      setTimeout(() => loadBots(), 2000);
    } else {
      showError('Erro ao configurar webhook', data);
      document.getElementById('webhookStatus').innerHTML = 
        `<div class="alert alert-danger mb-0">${data.message || 'Erro ao configurar'}</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = 'Setup Webhook';
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao configurar webhook');
  }
}

/**
 * Test webhook
 */
async function testWebhook(botId) {
  try {
    const btn = document.getElementById('btnTestWebhook');
    btn.disabled = true;
    btn.innerHTML = 'Testando...';

    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/test-webhook`, {
      method: 'POST',
    });

    const data = await response.json();

    if (response.ok) {
      toast('Webhook testado com sucesso!');
      document.getElementById('webhookStatus').innerHTML = 
        '<div class="alert alert-success mb-0">✓ Webhook funcionando corretamente</div>';
    } else {
      showError('Erro ao testar webhook', data);
      document.getElementById('webhookStatus').innerHTML = 
        `<div class="alert alert-danger mb-0">${data.message || 'Erro ao testar'}</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = 'Testar Webhook';
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao testar webhook');
  }
}

/**
 * Reset webhook
 */
async function resetWebhook(botId) {
  if (!confirm('Tem certeza que deseja remover o webhook?')) return;

  try {
    const btn = document.getElementById('btnResetWebhook');
    btn.disabled = true;
    btn.innerHTML = 'Removendo...';

    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/reset-webhook`, {
      method: 'POST',
    });

    const data = await response.json();

    if (response.ok) {
      toast('Webhook removido com sucesso!');
      document.getElementById('webhookStatus').innerHTML = 
        '<div class="alert alert-warning mb-0">⚠ Webhook removido</div>';
      setTimeout(() => loadBots(), 2000);
    } else {
      showError('Erro ao remover webhook', data);
    }

    btn.disabled = false;
    btn.innerHTML = 'Reset Webhook';
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao remover webhook');
  }
}

/**
 * View details
 */
async function viewDetails(botId) {
  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${botId}`);
    const data = await response.json();

    if (!response.ok) {
      showError('Erro ao carregar detalhes');
      return;
    }

    const bot = data.data;
    const detailsContent = document.getElementById('detailsContent');

    detailsContent.innerHTML = `
      <dl class="row">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8"><code>${bot.id}</code></dd>

        <dt class="col-sm-4">Nome</dt>
        <dd class="col-sm-8">${bot.name}</dd>

        <dt class="col-sm-4">Username</dt>
        <dd class="col-sm-8">@${bot.username}</dd>

        <dt class="col-sm-4">Bot Token</dt>
        <dd class="col-sm-8"><code style="word-break: break-all;">${bot.bot_token}</code></dd>

        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8"><span class="badge ${getStatusBadgeClass(bot.status)}">${bot.status}</span></dd>

        <dt class="col-sm-4">Descrição</dt>
        <dd class="col-sm-8">${bot.description || '—'}</dd>

        <dt class="col-sm-4">Portal ID</dt>
        <dd class="col-sm-8">${bot.portal_id}</dd>

        <dt class="col-sm-4">URL API</dt>
        <dd class="col-sm-8"><code style="word-break: break-all;">${bot.api_url}</code></dd>

        <dt class="col-sm-4">Grupo Telegram</dt>
        <dd class="col-sm-8">${bot.group_chat_id || '—'}</dd>

        <dt class="col-sm-4">Link Convite</dt>
        <dd class="col-sm-8">${bot.group_invite_link ? `<a href="${bot.group_invite_link}" target="_blank">${bot.group_invite_link}</a>` : '—'}</dd>

        <dt class="col-sm-4">URL Cadastro</dt>
        <dd class="col-sm-8">${bot.register_url ? `<a href="${bot.register_url}" target="_blank">${bot.register_url}</a>` : '—'}</dd>

        <dt class="col-sm-4">Debug Mode</dt>
        <dd class="col-sm-8">${bot.debug_mode ? '✓ Ativo' : '✗ Inativo'}</dd>

        <dt class="col-sm-4">Webhook Configurado</dt>
        <dd class="col-sm-8">
          ${bot.webhook_set_at 
            ? `<span class="badge bg-success">✓ ${new Date(bot.webhook_set_at).toLocaleString()}</span>` 
            : '<span class="badge bg-warning">✗ Não configurado</span>'
          }
        </dd>

        <dt class="col-sm-4">Webhook Testado</dt>
        <dd class="col-sm-8">
          ${bot.webhook_tested_at 
            ? `<span class="badge bg-success">✓ ${new Date(bot.webhook_tested_at).toLocaleString()}</span>` 
            : '<span class="badge bg-warning">✗ Não testado</span>'
          }
        </dd>

        <dt class="col-sm-4">Criado em</dt>
        <dd class="col-sm-8">${new Date(bot.created_at).toLocaleString()}</dd>

        <dt class="col-sm-4">Atualizado em</dt>
        <dd class="col-sm-8">${new Date(bot.updated_at).toLocaleString()}</dd>
      </dl>
    `;

    detailsModal.show();
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar detalhes');
  }
}

/**
 * Delete bot
 */
function deleteBot(botId) {
  deleteConfirmId = botId;
  document.getElementById('confirmMessage').textContent = 
    'Tem certeza que deseja deletar este bot? Esta ação não pode ser desfeita.';
  confirmModal.show();
}

/**
 * Confirm delete
 */
async function confirmDelete() {
  if (!deleteConfirmId) return;

  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${deleteConfirmId}`, {
      method: 'DELETE',
    });

    if (response.ok) {
      toast('Bot deletado com sucesso!');
      confirmModal.hide();
      loadBots();
    } else {
      const data = await response.json();
      showError('Erro ao deletar bot', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao deletar bot');
  }

  deleteConfirmId = null;
}

/**
 * Search bots
 */
function searchBots() {
  currentPage = 1;
  currentFilters = {
    q: document.getElementById('q').value || undefined,
    status: document.getElementById('status').value || undefined,
  };

  // Remove undefined filters
  Object.keys(currentFilters).forEach(key => 
    currentFilters[key] === undefined && delete currentFilters[key]
  );

  loadBots();
}

/**
 * Clear filters
 */
function clearFilters() {
  document.getElementById('q').value = '';
  document.getElementById('status').value = '';
  currentPage = 1;
  currentFilters = {};
  loadBots();
}

/**
 * Pagination
 */
function updatePagination(data) {
  const { current_page, last_page, total, from, to } = data;

  document.getElementById('paginationInfo').textContent = 
    `Mostrando ${from} a ${to} de ${total} bots`;

  document.getElementById('prevBtn').disabled = current_page === 1;
  document.getElementById('nextBtn').disabled = current_page === last_page;
}

function previousPage() {
  if (currentPage > 1) {
    currentPage--;
    loadBots();
    window.scrollTo(0, 0);
  }
}

function nextPage() {
  currentPage++;
  loadBots();
  window.scrollTo(0, 0);
}

/**
 * Clear form
 */
function clearForm() {
  document.getElementById('f_name').value = '';
  document.getElementById('f_username').value = '';
  document.getElementById('f_bot_token').value = '';
  document.getElementById('f_status').value = 'active';
  document.getElementById('f_description').value = '';
  document.getElementById('f_api_url').value = '';
  document.getElementById('f_api_key').value = '';
  document.getElementById('f_portal_id').value = '';
  document.getElementById('f_group_chat_id').value = '';
  document.getElementById('f_group_invite_link').value = '';
  document.getElementById('f_register_url').value = '';
  document.getElementById('f_debug_mode').checked = false;
  document.getElementById('webhookStatus').innerHTML = '';
}

/**
 * Show error
 */
function showError(message, data = null) {
  let errorMsg = message;

  if (data && data.errors) {
    const errors = Object.values(data.errors).flat();
    errorMsg += ': ' + errors.join(', ');
  } else if (data && data.message) {
    errorMsg = data.message;
  }

  toast(errorMsg, 'danger');
}
