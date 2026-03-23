@extends('layouts.app')

@section('title', 'Relatórios de Ganhos - Backoffice')
@section('page-title', 'Relatórios de Ganhos')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h5 mb-1">ComprovaBet — Envio de Relatórios Anuais</h2>
    <div class="text-muted small">Dispare relatórios de ganhos anuais por email para jogadores a partir da planilha de earnings</div>
  </div>
  <button class="btn btn-outline-secondary btn-sm" id="btnToggleHistory" type="button">Ver Histórico</button>
</div>

{{-- Status do arquivo de earnings --}}
<div class="card card-soft mb-4">
  <div class="card-body d-flex align-items-center gap-3 py-3">
    <div class="rounded-circle d-flex align-items-center justify-content-center" id="fileIcon"
         style="width: 44px; height: 44px; background: rgba(134,149,2,.12); flex-shrink: 0;">
      <span style="font-size: 1.3rem;">📄</span>
    </div>
    <div class="flex-grow-1">
      <div class="fw-semibold small" id="fileName">Verificando planilha...</div>
      <div class="text-muted" style="font-size: .78rem;" id="fileMeta"></div>
    </div>
    <div id="fileBadge"></div>
  </div>
</div>

{{-- Formulário de envio --}}
<div class="card card-soft mb-4">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <h6 class="card-title mb-0">Disparar Relatórios</h6>
      <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small fw-semibold" for="reportYear">Ano</label>
        <select class="form-select form-select-sm" id="reportYear" style="width: auto;">
          <option value="2025" selected>2025</option>
          <option value="2024">2024</option>
          <option value="2026">2026</option>
        </select>
      </div>
    </div>

    <div class="table-soft mb-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th style="width: 22%;">CPF</th>
              <th style="width: 18%;">Player ID</th>
              <th style="width: 32%;">Email</th>
              <th style="width: 20%;">Status</th>
              <th style="width: 8%; text-align: center;"></th>
            </tr>
          </thead>
          <tbody id="playersBody">
            {{-- Dynamic rows --}}
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <button class="btn btn-outline-secondary btn-sm" id="btnAddPlayer" type="button">+ Adicionar jogador</button>
      <button class="btn btn-primary" id="btnSend" type="button">Enviar Relatórios</button>
    </div>
  </div>
</div>

{{-- Resultado do envio --}}
<div id="resultsArea" class="d-none">
  <div class="card card-soft mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Resultado do envio</h6>
        <div id="resultsSummary" class="small text-muted"></div>
      </div>
      <div id="resultsContent"></div>
    </div>
  </div>
</div>

{{-- Histórico de lançamentos --}}
<div id="historyArea" class="d-none">
  <div class="card card-soft mb-4">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Histórico de Lançamentos</h6>
        <div class="d-flex align-items-center gap-2">
          <select class="form-select form-select-sm" id="historyYear" style="width: auto;">
            <option value="">Todos os anos</option>
            <option value="2025" selected>2025</option>
            <option value="2024">2024</option>
            <option value="2026">2026</option>
          </select>
          <select class="form-select form-select-sm" id="historyStatus" style="width: auto;">
            <option value="">Todos</option>
            <option value="queued">Na fila</option>
            <option value="sent">Enviado</option>
            <option value="sent_zeroed">Zerado</option>
            <option value="failed">Falhou</option>
          </select>
          <input type="text" class="form-control form-control-sm" id="historyPlayerId" placeholder="Player ID" style="width: 130px;">
          <button class="btn btn-sm btn-outline-primary" id="btnHistorySearch" type="button">Buscar</button>
        </div>
      </div>

      <div class="table-soft mb-3">
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0 align-middle">
            <thead>
              <tr>
                <th>Data</th>
                <th>Player ID</th>
                <th>CPF</th>
                <th>Email</th>
                <th>Ano</th>
                <th>Status</th>
                <th style="text-align: center;">Ações</th>
              </tr>
            </thead>
            <tbody id="historyBody">
              {{-- Dynamic rows --}}
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div id="historyInfo" class="small text-muted"></div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary" id="btnHistoryPrev" type="button" disabled>Anterior</button>
          <button class="btn btn-sm btn-outline-secondary" id="btnHistoryNext" type="button" disabled>Próxima</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const playersBody = document.getElementById('playersBody');
  const btnAdd = document.getElementById('btnAddPlayer');
  const btnSend = document.getElementById('btnSend');
  const resultsArea = document.getElementById('resultsArea');
  const resultsContent = document.getElementById('resultsContent');
  const resultsSummary = document.getElementById('resultsSummary');

  let rowCounter = 0;
  const rowStatuses = {};

  // --- File status ---
  async function checkFileStatus() {
    try {
      const res = await apiFetch('/api/v1/earnings-reports/status');
      if (!res.ok) {
        document.getElementById('fileName').textContent = 'Erro ao verificar arquivo';
        document.getElementById('fileBadge').innerHTML = '<span class="badge bg-danger-subtle text-danger">Erro</span>';
        return;
      }
      const data = await res.json();
      if (data.uploaded) {
        const dt = new Date(data.uploaded_at).toLocaleString('pt-BR');
        const sizeMb = (data.size / (1024 * 1024)).toFixed(1);
        document.getElementById('fileName').textContent = data.file;
        document.getElementById('fileMeta').textContent = sizeMb + ' MB — Última modificação: ' + dt;
        document.getElementById('fileBadge').innerHTML = '<span class="badge bg-success-subtle text-success">Pronta</span>';
      } else {
        document.getElementById('fileName').textContent = 'Planilha não encontrada';
        document.getElementById('fileMeta').textContent = 'O arquivo earnings.xlsx não está presente no repositório.';
        document.getElementById('fileBadge').innerHTML = '<span class="badge bg-warning-subtle text-warning">Ausente</span>';
        btnSend.disabled = true;
      }
    } catch (e) {
      document.getElementById('fileName').textContent = 'Erro de conexão';
      document.getElementById('fileBadge').innerHTML = '<span class="badge bg-danger-subtle text-danger">Offline</span>';
    }
  }
  checkFileStatus();

  // --- Player rows ---
  function addPlayerRow(cpf, playerId, email) {
    rowCounter++;
    const id = rowCounter;
    rowStatuses[id] = 'pending';

    const tr = document.createElement('tr');
    tr.id = 'row-' + id;
    tr.innerHTML = `
      <td><input type="text" class="form-control form-control-sm cpf-input" placeholder="000.000.000-00" data-row="${id}" maxlength="14"></td>
      <td><input type="text" class="form-control form-control-sm player-id-input" placeholder="Ex: 10310001" data-row="${id}"></td>
      <td><input type="email" class="form-control form-control-sm email-input" placeholder="email@exemplo.com" data-row="${id}"></td>
      <td class="status-cell" data-row="${id}"><span class="text-muted small">—</span></td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-row="${id}" title="Remover">&times;</button>
      </td>
    `;
    playersBody.appendChild(tr);

    if (cpf) tr.querySelector('.cpf-input').value = cpf;
    if (playerId) tr.querySelector('.player-id-input').value = playerId;
    if (email) tr.querySelector('.email-input').value = email;

    // CPF mask
    tr.querySelector('.cpf-input').addEventListener('input', function (e) {
      let v = e.target.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
      else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
      else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
      e.target.value = v;
    });

    // Remove button
    tr.querySelector('.btn-remove').addEventListener('click', function () {
      delete rowStatuses[id];
      tr.remove();
    });
  }

  // Start with one row
  addPlayerRow();

  btnAdd.addEventListener('click', function () {
    addPlayerRow();
  });

  // --- Update row status inline ---
  function setRowStatus(rowId, status) {
    const cell = document.querySelector('.status-cell[data-row="' + rowId + '"]');
    if (!cell) return;
    if (status === 'sent') {
      cell.innerHTML = '<span class="badge bg-success-subtle text-success">Enviado</span>';
    } else if (status === 'sent_zeroed') {
      cell.innerHTML = '<span class="badge bg-warning-subtle text-warning">Enviado (zerado)</span>';
    } else if (status === 'not_found') {
      cell.innerHTML = '<span class="badge bg-danger-subtle text-danger">Não encontrado</span>';
    } else if (status === 'sending') {
      cell.innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';
    } else if (status === 'error') {
      cell.innerHTML = '<span class="badge bg-danger-subtle text-danger">Erro</span>';
    }
  }

  // --- Send ---
  btnSend.addEventListener('click', function () {
    const rows = playersBody.querySelectorAll('tr');
    const players = [];
    const rowIds = [];

    let valid = true;
    rows.forEach(function (tr) {
      const cpf = tr.querySelector('.cpf-input').value.trim();
      const playerId = tr.querySelector('.player-id-input').value.trim();
      const email = tr.querySelector('.email-input').value.trim();
      const rowId = tr.querySelector('.cpf-input').dataset.row;

      if (!cpf || !playerId || !email) {
        valid = false;
        return;
      }

      players.push({ cpf: cpf, player_id: playerId, email: email });
      rowIds.push(rowId);
    });

    if (!valid || players.length === 0) {
      toast('Preencha todos os campos (CPF, Player ID e Email) de cada linha.', 'warning');
      return;
    }

    // Mark all rows as sending
    rowIds.forEach(function (rid) { setRowStatus(rid, 'sending'); });

    const year = parseInt(document.getElementById('reportYear').value);

    withLoading(btnSend, async () => {
      const res = await apiFetch('/api/v1/earnings-reports/send', {
        method: 'POST',
        body: JSON.stringify({ year: year, players: players }),
      });

      if (res.ok) {
        const data = await res.json();
        // Update inline status per row
        data.results.forEach(function (r, i) {
          if (rowIds[i]) setRowStatus(rowIds[i], r.status);
        });
        showResults(data);
        toast(data.message);
        // Refresh history if visible
        if (!historyArea.classList.contains('d-none')) loadHistory();
      } else {
        rowIds.forEach(function (rid) { setRowStatus(rid, 'error'); });
        await toastApiError(res, 'enviar relatórios');
      }
    });
  });

  function showResults(data) {
    resultsArea.classList.remove('d-none');

    const sent = data.results.filter(r => r.status === 'sent').length;
    const sentZeroed = data.results.filter(r => r.status === 'sent_zeroed').length;
    const notFound = data.results.filter(r => r.status === 'not_found').length;
    let summary = (sent + sentZeroed) + ' enviado(s)';
    if (sentZeroed > 0) summary += ' (' + sentZeroed + ' zerado(s))';
    if (notFound > 0) summary += ', ' + notFound + ' não encontrado(s)';
    resultsSummary.textContent = summary;

    let html = '<div class="table-soft"><table class="table table-sm mb-0"><thead><tr>' +
      '<th>Player ID</th><th>CPF</th><th>Email</th><th>Status</th></tr></thead><tbody>';

    data.results.forEach(function (r) {
      let statusBadge;
      if (r.status === 'sent') {
        statusBadge = '<span class="badge bg-success-subtle text-success">Enviado</span>';
      } else if (r.status === 'sent_zeroed') {
        statusBadge = '<span class="badge bg-warning-subtle text-warning">Enviado (zerado)</span>';
      } else {
        statusBadge = '<span class="badge bg-danger-subtle text-danger">Não encontrado</span>';
      }
      html += '<tr><td>' + escapeHtml(r.player_id) + '</td><td>' + escapeHtml(r.cpf) +
        '</td><td>' + escapeHtml(r.email) + '</td><td>' + statusBadge + '</td></tr>';
    });

    html += '</tbody></table></div>';
    resultsContent.innerHTML = html;
  }

  // =============================================
  // --- History section ---
  // =============================================
  const historyArea = document.getElementById('historyArea');
  const historyBody = document.getElementById('historyBody');
  const historyInfo = document.getElementById('historyInfo');
  const btnToggleHistory = document.getElementById('btnToggleHistory');
  const btnHistoryPrev = document.getElementById('btnHistoryPrev');
  const btnHistoryNext = document.getElementById('btnHistoryNext');
  const btnHistorySearch = document.getElementById('btnHistorySearch');

  let historyNextCursor = null;
  let historyPrevCursor = null;
  let historyVisible = false;

  btnToggleHistory.addEventListener('click', function () {
    historyVisible = !historyVisible;
    if (historyVisible) {
      historyArea.classList.remove('d-none');
      btnToggleHistory.textContent = 'Ocultar Histórico';
      loadHistory();
    } else {
      historyArea.classList.add('d-none');
      btnToggleHistory.textContent = 'Ver Histórico';
    }
  });

  btnHistorySearch.addEventListener('click', function () {
    loadHistory();
  });

  btnHistoryNext.addEventListener('click', function () {
    if (historyNextCursor) loadHistory(historyNextCursor);
  });

  btnHistoryPrev.addEventListener('click', function () {
    if (historyPrevCursor) loadHistory(null, historyPrevCursor);
  });

  function historyStatusBadge(status, zeroed) {
    if (zeroed && (status === 'queued' || status === 'sent')) {
      if (status === 'sent') return '<span class="badge bg-warning-subtle text-warning">Enviado (zerado)</span>';
      return '<span class="badge bg-secondary-subtle text-secondary">Na fila (zerado)</span>';
    }
    if (status === 'sent') return '<span class="badge bg-success-subtle text-success">Enviado</span>';
    if (status === 'queued') return '<span class="badge bg-secondary-subtle text-secondary">Na fila</span>';
    if (status === 'failed') return '<span class="badge bg-danger-subtle text-danger">Falhou</span>';
    return '<span class="badge bg-secondary-subtle text-secondary">' + escapeHtml(status) + '</span>';
  }

  async function loadHistory(nextCursor, prevCursor) {
    const year = document.getElementById('historyYear').value;
    const status = document.getElementById('historyStatus').value;
    const playerId = document.getElementById('historyPlayerId').value.trim();

    let url = '/api/v1/earnings-reports/history?';
    const params = [];
    if (year) params.push('year=' + encodeURIComponent(year));
    if (status) params.push('status=' + encodeURIComponent(status));
    if (playerId) params.push('player_id=' + encodeURIComponent(playerId));
    if (nextCursor) params.push('cursor=' + encodeURIComponent(nextCursor));
    if (prevCursor) params.push('cursor=' + encodeURIComponent(prevCursor));
    url += params.join('&');

    tableLoading(historyBody, 7);

    try {
      const res = await apiFetch(url);
      if (!res.ok) {
        tableError(historyBody, 7, res.status);
        return;
      }
      const data = await res.json();

      historyNextCursor = data.next_cursor;
      historyPrevCursor = data.prev_cursor;
      btnHistoryNext.disabled = !historyNextCursor;
      btnHistoryPrev.disabled = !historyPrevCursor;

      if (!data.data || data.data.length === 0) {
        tableEmpty(historyBody, 7, 'Nenhum registro encontrado.');
        historyInfo.textContent = '';
        return;
      }

      historyInfo.textContent = 'Exibindo ' + data.data.length + ' registro(s)';

      let html = '';
      data.data.forEach(function (log) {
        const dt = new Date(log.created_at).toLocaleString('pt-BR');
        const canResend = log.status === 'failed' || log.zeroed;
        html += '<tr>' +
          '<td class="small">' + escapeHtml(dt) + '</td>' +
          '<td>' + escapeHtml(log.player_id) + '</td>' +
          '<td>' + escapeHtml(log.cpf) + '</td>' +
          '<td>' + escapeHtml(log.email) + '</td>' +
          '<td>' + log.year + '</td>' +
          '<td>' + historyStatusBadge(log.status, log.zeroed) + '</td>' +
          '<td class="text-center">' +
            (canResend
              ? '<button class="btn btn-sm btn-outline-primary btn-resend" data-log-id="' + log.id + '" title="Reenviar">Reenviar</button>'
              : '<span class="text-muted small">—</span>') +
          '</td>' +
          '</tr>';
      });
      historyBody.innerHTML = html;

    } catch (e) {
      tableError(historyBody, 7, 'Erro de conexão');
    }
  }

  // --- Resend from history ---
  historyBody.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-resend');
    if (!btn) return;

    const logId = btn.dataset.logId;

    await withLoading(btn, async () => {
      const res = await apiFetch('/api/v1/earnings-reports/history/' + logId + '/resend', {
        method: 'POST',
      });

      if (res.ok) {
        const data = await res.json();
        toast(data.message);
        loadHistory();
      } else {
        await toastApiError(res, 'reenviar relatório');
      }
    });
  });
});
</script>
@endpush
