/**
 * Bot Statistics Management
 * Consome as rotas da API de Estatísticas de Bots
 */

let filterDays = 30;
let botId = null;
let activityChart = null;
let distributionChart = null;

// Get bot ID from URL
function extractBotIdFromUrl() {
  const match = window.location.pathname.match(/\/telegram-bots\/(\d+)\/statistics/);
  return match ? parseInt(match[1]) : null;
}

botId = extractBotIdFromUrl();

// Event Listeners
document.getElementById('btnReload').addEventListener('click', loadAllData);
document.getElementById('btnExport').addEventListener('click', exportData);
document.getElementById('btnApplyFilter').addEventListener('click', () => {
  filterDays = parseInt(document.getElementById('filterDays').value);
  loadAllData();
});

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
  loadBotInfo();
  loadAllData();
});

/**
 * Load all statistics
 */
async function loadAllData() {
  if (!botId) return;

  loadSummary();
  loadChartData();
  loadValidatedUsers();
  loadFailedUsers();
  loadMessages();
}

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
 * Load summary statistics
 */
async function loadSummary() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics?days=${filterDays}`
    );
    const data = await response.json();

    if (response.ok) {
      const stats = data.data;

      document.getElementById('statMessagesSent').textContent = 
        (stats.total_messages_sent || 0).toLocaleString();
      document.getElementById('statMessagesReceived').textContent = 
        (stats.total_messages_received || 0).toLocaleString();
      document.getElementById('statUsersNew').textContent = 
        (stats.users_new || 0).toLocaleString();
      document.getElementById('statSuccessRate').textContent = 
        `${(stats.validation_success_rate || 0).toFixed(1)}%`;
      document.getElementById('statValidationSuccess').textContent = 
        (stats.validations_success || 0).toLocaleString();
      document.getElementById('statValidationFailed').textContent = 
        (stats.validations_failed || 0).toLocaleString();
      document.getElementById('statTotalMessages').textContent = 
        `${((stats.total_messages_sent || 0) + (stats.total_messages_received || 0)).toLocaleString()}`;
      document.getElementById('statLastUpdate').textContent = 
        new Date().toLocaleString();
    } else {
      console.error('Erro ao carregar summary:', data);
    }
  } catch (error) {
    console.error('Erro:', error);
  }
}

/**
 * Load chart data
 */
async function loadChartData() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics/chart?days=${filterDays}`
    );
    const data = await response.json();

    if (response.ok) {
      const chartData = data.data;
      renderActivityChart(chartData);
      renderDistributionChart(chartData);
    } else {
      console.error('Erro ao carregar chart data:', data);
    }
  } catch (error) {
    console.error('Erro:', error);
  }
}

/**
 * Render activity chart
 */
function renderActivityChart(chartData) {
  const ctx = document.getElementById('activityChart');
  
  if (activityChart) {
    activityChart.destroy();
  }

  const labels = chartData.dates || [];
  const messagesSent = chartData.messages_sent || [];
  const messagesReceived = chartData.messages_received || [];

  activityChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Mensagens Enviadas',
          data: messagesSent,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          tension: 0.4,
          fill: true,
        },
        {
          label: 'Mensagens Recebidas',
          data: messagesReceived,
          borderColor: '#198754',
          backgroundColor: 'rgba(25, 135, 84, 0.1)',
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}

/**
 * Render distribution chart
 */
function renderDistributionChart(chartData) {
  const ctx = document.getElementById('distributionChart');

  if (distributionChart) {
    distributionChart.destroy();
  }

  const validationSuccess = chartData.validations_success || 0;
  const validationFailed = chartData.validations_failed || 0;

  distributionChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Sucesso', 'Falhas'],
      datasets: [
        {
          data: [validationSuccess, validationFailed],
          backgroundColor: ['#198754', '#dc3545'],
          borderColor: '#fff',
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
        },
      },
    },
  });
}

/**
 * Load validated users
 */
async function loadValidatedUsers() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics/validated-users`
    );
    const data = await response.json();

    if (response.ok) {
      displayValidatedUsers(data.data);
    } else {
      console.error('Erro ao carregar usuários validados:', data);
    }
  } catch (error) {
    console.error('Erro:', error);
  }
}

/**
 * Display validated users
 */
function displayValidatedUsers(users) {
  const table = document.getElementById('validatedUsersTable');

  if (!users || users.length === 0) {
    table.innerHTML = '<tr><td colspan="5" class="text-muted p-4 text-center">Nenhum usuário validado</td></tr>';
    return;
  }

  table.innerHTML = users.map(user => `
    <tr>
      <td><code>${user.telegram_user_id}</code></td>
      <td>${user.first_name || '—'}</td>
      <td>${user.email || '—'}</td>
      <td>
        <span class="badge bg-success">${user.status}</span>
      </td>
      <td>
        <small>${new Date(user.validated_at).toLocaleString()}</small>
      </td>
    </tr>
  `).join('');
}

/**
 * Load failed users
 */
async function loadFailedUsers() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics/failed-users`
    );
    const data = await response.json();

    if (response.ok) {
      displayFailedUsers(data.data);
    } else {
      console.error('Erro ao carregar usuários com falha:', data);
    }
  } catch (error) {
    console.error('Erro:', error);
  }
}

/**
 * Display failed users
 */
function displayFailedUsers(users) {
  const table = document.getElementById('failedUsersTable');

  if (!users || users.length === 0) {
    table.innerHTML = '<tr><td colspan="5" class="text-muted p-4 text-center">Nenhum usuário com falha</td></tr>';
    return;
  }

  table.innerHTML = users.map(user => `
    <tr>
      <td><code>${user.telegram_user_id}</code></td>
      <td>${user.first_name || '—'}</td>
      <td>${user.email || '—'}</td>
      <td>
        <span class="badge bg-danger">${user.status}</span>
      </td>
      <td>
        <small>${new Date(user.updated_at).toLocaleString()}</small>
      </td>
    </tr>
  `).join('');
}

/**
 * Load messages log
 */
async function loadMessages() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics/messages`
    );
    const data = await response.json();

    if (response.ok) {
      displayMessages(data.data);
    } else {
      console.error('Erro ao carregar log de mensagens:', data);
    }
  } catch (error) {
    console.error('Erro:', error);
  }
}

/**
 * Display messages
 */
function displayMessages(messages) {
  const table = document.getElementById('messagesLogTable');

  if (!messages || messages.length === 0) {
    table.innerHTML = '<tr><td colspan="5" class="text-muted p-4 text-center">Nenhuma mensagem registrada</td></tr>';
    return;
  }

  table.innerHTML = messages.slice(0, 100).map(msg => {
    const directionBadge = msg.direction === 'incoming' 
      ? '<span class="badge bg-info">📥 Recebida</span>'
      : '<span class="badge bg-primary">📤 Enviada</span>';

    const statusBadge = msg.status === 'sent'
      ? '<span class="badge bg-success">✓ Enviada</span>'
      : msg.status === 'delivered'
      ? '<span class="badge bg-success">✓ Entregue</span>'
      : '<span class="badge bg-danger">✗ Erro</span>';

    const content = msg.content ? 
      (msg.content.length > 50 ? msg.content.substring(0, 50) + '...' : msg.content)
      : '—';

    return `
      <tr>
        <td><code>${msg.id}</code></td>
        <td>${directionBadge}</td>
        <td title="${msg.content}">${content}</td>
        <td>${statusBadge}</td>
        <td><small>${new Date(msg.created_at).toLocaleString()}</small></td>
      </tr>
    `;
  }).join('');
}

/**
 * Export data
 */
async function exportData() {
  try {
    const response = await apiFetch(
      `/api/v1/telegram-bots/${botId}/statistics/export?days=${filterDays}`
    );

    if (!response.ok) {
      showError('Erro ao exportar dados');
      return;
    }

    // Get filename from header or use default
    const disposition = response.headers.get('content-disposition');
    let filename = `bot-statistics-${botId}-${new Date().toISOString().split('T')[0]}.csv`;

    if (disposition && disposition.indexOf('attachment') !== -1) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(disposition);
      if (matches != null && matches[1]) {
        filename = matches[1].replace(/['"]/g, '');
      }
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    toast('Dados exportados com sucesso!');
  } catch (error) {
    console.error('Erro:', error);
    showError('Erro ao exportar dados');
  }
}

/**
 * Show error
 */
function showError(message) {
  toast(message, 'danger');
}
