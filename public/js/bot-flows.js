/**
 * Bot Flows Management
 * Consome as rotas da API de Fluxos de Bots
 */

let currentPage = 1;
let currentFilters = {};
let editingFlowId = null;
let deleteConfirmId = null;
let currentSteps = [];
let stepCounter = 1;
let botId = null;

// Get bot ID from URL
function extractBotIdFromUrl() {
  const match = window.location.pathname.match(/\/telegram-bots\/(\d+)\/flows/);
  return match ? parseInt(match[1]) : null;
}

botId = extractBotIdFromUrl();

// DOM Elements
const tbody = document.getElementById('tbody');
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

// Event Listeners
document.getElementById('btnNew').addEventListener('click', openCreateModal);
document.getElementById('btnReload').addEventListener('click', loadFlows);
document.getElementById('btnSearch').addEventListener('click', searchFlows);
document.getElementById('btnClear').addEventListener('click', clearFilters);
document.getElementById('btnSave').addEventListener('click', saveFlowData);
document.getElementById('btnConfirmDelete').addEventListener('click', confirmDelete);
document.getElementById('btnAddStep').addEventListener('click', addStep);
document.getElementById('prevBtn').addEventListener('click', () => previousPage());
document.getElementById('nextBtn').addEventListener('click', () => nextPage());

document.getElementById('q').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') searchFlows();
});

// Load flows on page load
document.addEventListener('DOMContentLoaded', () => {
  loadBotInfo();
  loadFlows();
});

/**
 * Load bot info
 */
async function loadBotInfo() {
  if (!botId) return;

  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${botId}`);
    const data = await response.json();

    if (response.ok) {
      const bot = data.data;
      document.getElementById('botInfo').style.display = 'block';
      document.getElementById('botName').textContent = bot.name;
      document.getElementById('botUsername').textContent = bot.username;
    }
  } catch (error) {
    console.error('Erro ao carregar info do bot:', error);
  }
}

/**
 * Load flows list
 */
async function loadFlows() {
  if (!botId) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted p-4">Bot não encontrado</td></tr>';
    return;
  }

  try {
    const params = new URLSearchParams({
      page: currentPage,
      ...currentFilters,
    });

    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/flows?${params}`);
    const data = await response.json();

    if (response.ok) {
      displayFlows(data.data);
      updatePagination(data);
    } else {
      showError('Erro ao carregar fluxos');
      console.error(data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar fluxos');
  }
}

/**
 * Display flows in table
 */
function displayFlows(flows) {
  if (!flows || flows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted p-4">Nenhum fluxo encontrado</td></tr>';
    return;
  }

  tbody.innerHTML = flows.map(flow => `
    <tr>
      <td><code>${flow.id}</code></td>
      <td><strong>${flow.name}</strong></td>
      <td><span class="badge bg-info">${flow.version}</span></td>
      <td>
        <span class="badge ${getStatusBadgeClass(flow.status)}">
          ${flow.status}
        </span>
      </td>
      <td>
        ${flow.is_default 
          ? '<span class="badge bg-success">✓ Padrão</span>' 
          : '—'
        }
      </td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-info action-btn" onclick="previewFlow(${flow.id})">Preview</button>
        <button class="btn btn-sm btn-outline-primary action-btn" onclick="editFlow(${flow.id})">Editar</button>
        <button class="btn btn-sm btn-outline-secondary action-btn" onclick="duplicateFlow(${flow.id})">Duplicar</button>
        ${flow.status === 'draft' 
          ? `<button class="btn btn-sm btn-outline-success action-btn" onclick="publishFlow(${flow.id})">Publicar</button>` 
          : ''
        }
        <button class="btn btn-sm btn-outline-danger action-btn" onclick="deleteFlow(${flow.id})">Deletar</button>
      </td>
    </tr>
  `).join('');
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(status) {
  const classes = {
    'draft': 'bg-secondary',
    'active': 'bg-success',
    'archived': 'bg-warning',
  };
  return classes[status] || 'bg-secondary';
}

/**
 * Open create modal
 */
function openCreateModal() {
  editingFlowId = null;
  currentSteps = [];
  stepCounter = 1;
  clearForm();
  document.getElementById('editTitle').textContent = 'Novo Fluxo';
  renderSteps();
  editModal.show();
}

/**
 * Edit flow
 */
async function editFlow(flowId) {
  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/flows/${flowId}`);
    const data = await response.json();

    if (!response.ok) {
      showError('Erro ao carregar fluxo');
      return;
    }

    const flow = data.data;
    editingFlowId = flowId;

    // Populate form
    document.getElementById('f_name').value = flow.name || '';
    document.getElementById('f_description').value = flow.description || '';
    document.getElementById('f_status').value = flow.status || 'draft';
    document.getElementById('f_version').value = flow.version || 1;
    document.getElementById('f_is_default').checked = flow.is_default || false;

    // Load steps
    currentSteps = flow.flow_data || [];
    stepCounter = currentSteps.length + 1;

    document.getElementById('editTitle').textContent = 'Editar Fluxo';
    renderSteps();
    editModal.show();
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar fluxo');
  }
}

/**
 * Add step
 */
function addStep() {
  const stepId = `step-${Date.now()}`;
  currentSteps.push({
    id: stepId,
    type: 'message',
    order: currentSteps.length + 1,
    data: {},
  });

  renderSteps();
}

/**
 * Render steps
 */
function renderSteps() {
  const container = document.getElementById('flowStepsContainer');

  if (currentSteps.length === 0) {
    container.innerHTML = '<div class="text-muted text-center py-4">Nenhum step adicionado. Clique em "+ Adicionar Step"</div>';
    return;
  }

  container.innerHTML = currentSteps.map((step, idx) => {
    const template = document.getElementById('stepTemplate');
    const clone = template.content.cloneNode(true);

    const stepCard = clone.querySelector('.step-card');
    stepCard.dataset.stepId = step.id;
    stepCard.classList.add(step.type);

    clone.querySelector('.step-title').textContent = `Step ${idx + 1} - ${step.type}`;
    clone.querySelector('.stepType').value = step.type;

    // Render step-specific inputs
    const dataContainer = clone.querySelector('.stepDataContainer');
    dataContainer.innerHTML = getStepDataFields(step.type, step.data);

    // Event listeners
    clone.querySelector('.stepType').addEventListener('change', (e) => {
      currentSteps[idx].type = e.target.value;
      renderSteps();
    });

    clone.querySelector('.removeStepBtn').addEventListener('click', () => {
      currentSteps.splice(idx, 1);
      renderSteps();
    });

    clone.querySelector('.moveUpBtn').addEventListener('click', () => {
      if (idx > 0) {
        [currentSteps[idx], currentSteps[idx - 1]] = [currentSteps[idx - 1], currentSteps[idx]];
        renderSteps();
      }
    });

    clone.querySelector('.moveDownBtn').addEventListener('click', () => {
      if (idx < currentSteps.length - 1) {
        [currentSteps[idx], currentSteps[idx + 1]] = [currentSteps[idx + 1], currentSteps[idx]];
        renderSteps();
      }
    });

    // Save data inputs
    const inputs = clone.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      input.addEventListener('change', (e) => {
        const key = input.name || input.id;
        if (input.type === 'checkbox') {
          currentSteps[idx].data[key] = input.checked;
        } else {
          currentSteps[idx].data[key] = input.value;
        }
      });
    });

    return clone;
  }).map(el => {
    const div = document.createElement('div');
    div.appendChild(el);
    return div.innerHTML;
  }).join('');
}

/**
 * Get step data fields
 */
function getStepDataFields(type, data) {
  switch (type) {
    case 'message':
      return `
        <div class="col-12">
          <label class="form-label">Conteúdo da Mensagem</label>
          <textarea class="form-control" name="content" rows="3" placeholder="Conteúdo da mensagem...">${data.content || ''}</textarea>
          <small class="form-text">Suporta Markdown. Use {bot_name}, {first_name}, etc para variáveis</small>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label">Parse Mode</label>
          <select class="form-select" name="parse_mode">
            <option value="Markdown" ${data.parse_mode === 'Markdown' ? 'selected' : ''}>Markdown</option>
            <option value="HTML" ${data.parse_mode === 'HTML' ? 'selected' : ''}>HTML</option>
            <option value="" ${!data.parse_mode ? 'selected' : ''}>Sem formatação</option>
          </select>
        </div>
      `;

    case 'buttons':
      return `
        <div class="col-12">
          <label class="form-label">Mensagem</label>
          <textarea class="form-control" name="message" rows="2" placeholder="Mensagem com botões...">${data.message || ''}</textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Botões (JSON)</label>
          <textarea class="form-control" name="buttons" rows="4" placeholder='[{"text":"Botão 1","callback_data":"btn1"},...]'>${JSON.stringify(data.buttons || [], null, 2)}</textarea>
          <small class="form-text">JSON array com {text, callback_data} ou {text, url}</small>
        </div>
      `;

    case 'input':
      return `
        <div class="col-12">
          <label class="form-label">Mensagem de Prompt</label>
          <textarea class="form-control" name="prompt" rows="2" placeholder="O que você deseja fazer?">${data.prompt || ''}</textarea>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label">Tipo de Validação</label>
          <select class="form-select" name="validate_type">
            <option value="text" ${data.validate_type === 'text' ? 'selected' : ''}>text</option>
            <option value="email" ${data.validate_type === 'email' ? 'selected' : ''}>email</option>
            <option value="number" ${data.validate_type === 'number' ? 'selected' : ''}>number</option>
            <option value="cpf" ${data.validate_type === 'cpf' ? 'selected' : ''}>cpf</option>
          </select>
        </div>
      `;

    case 'validation':
      return `
        <div class="col-12 col-lg-6">
          <label class="form-label">Tipo de Validação</label>
          <select class="form-select" name="type">
            <option value="email_api" ${data.type === 'email_api' ? 'selected' : ''}>Email via API</option>
            <option value="email_format" ${data.type === 'email_format' ? 'selected' : ''}>Formato de Email</option>
            <option value="cpf" ${data.type === 'cpf' ? 'selected' : ''}>CPF</option>
          </select>
        </div>
        <div class="col-12">
          <small class="form-text">O input anterior será validado com base no tipo selecionado</small>
        </div>
      `;

    case 'condition':
      return `
        <div class="col-12 col-lg-6">
          <label class="form-label">Tipo de Condição</label>
          <select class="form-select" name="condition_type">
            <option value="validation_success" ${data.condition_type === 'validation_success' ? 'selected' : ''}>Se validação sucedeu</option>
            <option value="validation_failed" ${data.condition_type === 'validation_failed' ? 'selected' : ''}>Se validação falhou</option>
          </select>
        </div>
      `;

    case 'action':
      return `
        <div class="col-12 col-lg-6">
          <label class="form-label">Tipo de Ação</label>
          <select class="form-select" name="action">
            <option value="send_group_link" ${data.action === 'send_group_link' ? 'selected' : ''}>Enviar Link do Grupo</option>
            <option value="offer_registration" ${data.action === 'offer_registration' ? 'selected' : ''}>Oferecer Cadastro</option>
            <option value="mark_validated" ${data.action === 'mark_validated' ? 'selected' : ''}>Marcar como Validado</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Mensagem</label>
          <textarea class="form-control" name="message" rows="2" placeholder="Mensagem da ação...">${data.message || ''}</textarea>
        </div>
      `;

    default:
      return '<div class="col-12 text-muted">Configure os detalhes do step acima</div>';
  }
}

/**
 * Save flow data
 */
async function saveFlowData() {
  if (currentSteps.length === 0) {
    toast('Adicione pelo menos um step ao fluxo', 'warning');
    return;
  }

  const formData = {
    name: document.getElementById('f_name').value,
    description: document.getElementById('f_description').value,
    status: document.getElementById('f_status').value,
    is_default: document.getElementById('f_is_default').checked,
    flow_data: currentSteps.map((step, idx) => ({
      ...step,
      order: idx + 1,
    })),
  };

  try {
    const url = editingFlowId 
      ? `/api/v1/telegram-bots/${botId}/flows/${editingFlowId}`
      : `/api/v1/telegram-bots/${botId}/flows`;
    
    const method = editingFlowId ? 'PUT' : 'POST';

    const response = await apiFetch(url, {
      method,
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (response.ok) {
      toast('Fluxo salvo com sucesso!');
      editModal.hide();
      loadFlows();
    } else {
      showError('Erro ao salvar fluxo', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao salvar fluxo');
  }
}

/**
 * Preview flow
 */
async function previewFlow(flowId) {
  try {
    const response = await apiFetch(`/api/v1/telegram-bots/${botId}/flows/${flowId}`);
    const data = await response.json();

    if (!response.ok) {
      showError('Erro ao carregar fluxo');
      return;
    }

    const flow = data.data;
    const previewContent = document.getElementById('previewContent');

    let html = `
      <h6>${flow.name}</h6>
      <p class="text-muted small mb-3">${flow.description || ''}</p>
      <hr>
    `;

    flow.flow_data.forEach((step, idx) => {
      html += `
        <div class="mb-3 p-2 bg-light rounded">
          <strong class="d-block mb-2">Step ${idx + 1}: ${step.type}</strong>
      `;

      switch (step.type) {
        case 'message':
          html += `<div class="text-wrap"><em>${step.data.content || ''}</em></div>`;
          break;
        case 'buttons':
          html += `<div class="mb-2"><em>${step.data.message || ''}</em></div>`;
          if (step.data.buttons) {
            html += '<div class="d-flex flex-wrap gap-1">';
            step.data.buttons.forEach(btn => {
              html += `<button class="btn btn-sm btn-outline-primary" disabled>${btn.text}</button>`;
            });
            html += '</div>';
          }
          break;
        case 'input':
          html += `<div class="text-wrap"><em>${step.data.prompt || ''}</em></div>
                   <input type="text" class="form-control form-control-sm mt-2" disabled placeholder="Resposta do usuário...">`;
          break;
        case 'validation':
          html += `<div class="alert alert-info mb-0">Validar: ${step.data.type}</div>`;
          break;
        case 'action':
          html += `<div class="alert alert-success mb-0">Ação: ${step.data.action}</div>`;
          break;
      }

      html += '</div>';
    });

    previewContent.innerHTML = html;
    previewModal.show();
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao carregar preview');
  }
}

/**
 * Duplicate flow
 */
async function duplicateFlow(flowId) {
  if (!confirm('Tem certeza que deseja duplicar este fluxo?')) return;

  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/flows/${flowId}/duplicate`,
      { method: 'POST' }
    );

    const data = await response.json();

    if (response.ok) {
      toast('Fluxo duplicado com sucesso!');
      loadFlows();
    } else {
      showError('Erro ao duplicar fluxo', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao duplicar fluxo');
  }
}

/**
 * Publish flow
 */
async function publishFlow(flowId) {
  if (!confirm('Tem certeza que deseja publicar este fluxo? Versões antigas serão arquivadas.')) return;

  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/flows/${flowId}/publish`,
      { method: 'POST' }
    );

    const data = await response.json();

    if (response.ok) {
      toast('Fluxo publicado com sucesso!');
      loadFlows();
    } else {
      showError('Erro ao publicar fluxo', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao publicar fluxo');
  }
}

/**
 * Delete flow
 */
function deleteFlow(flowId) {
  deleteConfirmId = flowId;
  document.getElementById('confirmMessage').textContent = 
    'Tem certeza que deseja deletar este fluxo?';
  confirmModal.show();
}

/**
 * Confirm delete
 */
async function confirmDelete() {
  if (!deleteConfirmId) return;

  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/flows/${deleteConfirmId}`,
      { method: 'DELETE' }
    );

    if (response.ok) {
      toast('Fluxo deletado com sucesso!');
      confirmModal.hide();
      loadFlows();
    } else {
      const data = await response.json();
      showError('Erro ao deletar fluxo', data);
    }
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao deletar fluxo');
  }

  deleteConfirmId = null;
}

/**
 * Search flows
 */
function searchFlows() {
  currentPage = 1;
  currentFilters = {
    q: document.getElementById('q').value || undefined,
    status: document.getElementById('status').value || undefined,
  };

  Object.keys(currentFilters).forEach(key => 
    currentFilters[key] === undefined && delete currentFilters[key]
  );

  loadFlows();
}

/**
 * Clear filters
 */
function clearFilters() {
  document.getElementById('q').value = '';
  document.getElementById('status').value = '';
  currentPage = 1;
  currentFilters = {};
  loadFlows();
}

/**
 * Pagination
 */
function updatePagination(data) {
  const { current_page, last_page, total, from, to } = data;

  document.getElementById('paginationInfo').textContent = 
    `Mostrando ${from} a ${to} de ${total} fluxos`;

  document.getElementById('prevBtn').disabled = current_page === 1;
  document.getElementById('nextBtn').disabled = current_page === last_page;
}

function previousPage() {
  if (currentPage > 1) {
    currentPage--;
    loadFlows();
    window.scrollTo(0, 0);
  }
}

function nextPage() {
  currentPage++;
  loadFlows();
  window.scrollTo(0, 0);
}

/**
 * Clear form
 */
function clearForm() {
  document.getElementById('f_name').value = '';
  document.getElementById('f_description').value = '';
  document.getElementById('f_status').value = 'draft';
  document.getElementById('f_version').value = '1';
  document.getElementById('f_is_default').checked = false;
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
