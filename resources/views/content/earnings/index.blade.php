@extends('layouts.app')

@section('title', 'Relatórios de Ganhos - Backoffice')
@section('page-title', 'Relatórios de Ganhos')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">ComprovaBet — Envio de Relatórios Anuais</h2>
    <div class="text-muted small">Dispare relatórios de ganhos anuais por email para jogadores a partir da planilha de earnings</div>
  </div>
  <button class="btn btn-outline-secondary btn-sm" id="btnExportLog" type="button">
    Exportar Relatório de Envios (PDF)
  </button>
</div>

{{-- Status do arquivo de earnings --}}
<div class="card card-soft mb-3">
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
<div class="card card-soft mb-3">
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
  <div class="card card-soft mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="card-title mb-0">Resultado do envio</h6>
        <div id="resultsSummary" class="small text-muted"></div>
      </div>
      <div id="resultsContent"></div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js" integrity="sha512-MpDFIChbcXl2QgipQrt1VcPHMldRILetapBEmo2jLETXCwSBiMNJxg6LoaFqgSXewNjon06CY6fxEaxMQjKMNw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const playersBody = document.getElementById('playersBody');
  const btnAdd = document.getElementById('btnAddPlayer');
  const btnSend = document.getElementById('btnSend');
  const resultsArea = document.getElementById('resultsArea');
  const resultsContent = document.getElementById('resultsContent');
  const resultsSummary = document.getElementById('resultsSummary');

  const btnExport = document.getElementById('btnExportLog');

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
      } else {
        rowIds.forEach(function (rid) { setRowStatus(rid, 'error'); });
        await toastApiError(res, 'enviar relatórios');
      }
    });
  });

  // --- Export PDF ---
  function buildReportHtml(data) {
    const year = document.getElementById('reportYear').value;
    const successCount = data.success.length;
    const problemsCount = data.problems.length;

    let successRows = '';
    data.success.forEach(function (e) {
      const income = e.net_income !== null ? parseFloat(e.net_income) : null;
      const incomeClass = income !== null && income >= 0 ? 'income-positive' : 'income-negative';
      const incomeText = income !== null ? income.toFixed(2) : '—';
      successRows += '<tr>' +
        '<td>' + escapeHtml(e.player_id) + '</td>' +
        '<td>' + escapeHtml(e.email || '—') + '</td>' +
        '<td class="' + incomeClass + '">' + escapeHtml(incomeText) + '</td>' +
        '<td class="status-success">Enviado</td>' +
        '</tr>';
    });

    let problemsSection = '';
    if (problemsCount > 0) {
      let problemRows = '';
      data.problems.forEach(function (e) {
        problemRows += '<tr>' +
          '<td>' + escapeHtml(e.player_id) + '</td>' +
          '<td>' + escapeHtml(e.email || '—') + '</td>' +
          '<td class="status-error">' + escapeHtml(e.reason || 'Falha - Não Encontrado') + '</td>' +
          '</tr>';
      });
      problemsSection = '<h2>\u26A0\uFE0F Jogadores Não Encontrados na Planilha</h2>' +
        '<table><thead><tr><th>Player ID</th><th>Email Buscado</th><th>Status</th></tr></thead>' +
        '<tbody>' + problemRows + '</tbody></table>';
    }

    return '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8" />' +
      '<meta name="viewport" content="width=device-width, initial-scale=1.0" />' +
      '<title>Sumário de Logs - Relatórios de Ganhos (' + escapeHtml(year) + ')</title>' +
      '<style>' +
      'body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background-color:#f4f7f6;color:#333;margin:0;padding:20px;}' +
      '.container{max-width:900px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}' +
      'h1,h2{color:#2c3e50;}' +
      '.summary-cards{display:flex;gap:20px;margin-bottom:30px;}' +
      '.card{flex:1;padding:20px;border-radius:8px;color:white;text-align:center;}' +
      '.card.success{background-color:#27ae60;}' +
      '.card.warning{background-color:#e67e22;}' +
      '.card h3{margin:0;font-size:2em;}' +
      '.card p{margin:5px 0 0;font-size:1.1em;}' +
      'table{width:100%;border-collapse:collapse;margin-bottom:30px;}' +
      'th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}' +
      'th{background-color:#ecf0f1;color:#333;}' +
      'tr:hover{background-color:#f9f9f9;}' +
      '.status-success{color:#27ae60;font-weight:bold;}' +
      '.status-error{color:#c0392b;font-weight:bold;}' +
      '.income-positive{color:#27ae60;}' +
      '.income-negative{color:#c0392b;}' +
      '</style></head><body><div class="container">' +
      '<h1>Sumário de Disparos de Email (Betaki)</h1>' +
      '<p>Resumo da extração de logs referente ao ano-base de ' + escapeHtml(year) + '. ' +
      'Registros duplicados do mesmo usuário nas requisições foram agrupados para melhor visualização.</p>' +
      '<div class="summary-cards">' +
      '<div class="card success"><h3>' + successCount + '</h3><p>Emails Enviados com Sucesso</p></div>' +
      '<div class="card warning"><h3>' + problemsCount + '</h3><p>Jogadores Não Encontrados</p></div>' +
      '</div>' +
      '<h2>\u2705 Emails Despachados com Sucesso</h2>' +
      '<table><thead><tr><th>Player ID</th><th>Email Destino</th><th>Net Income (BRL)</th><th>Status</th></tr></thead>' +
      '<tbody>' + (successRows || '<tr><td colspan="4" style="text-align:center;color:#999;">Nenhum registro encontrado.</td></tr>') + '</tbody></table>' +
      problemsSection +
      '</div></body></html>';
  }

  btnExport.addEventListener('click', function () {
    withLoading(btnExport, async () => {
      try {
        const res = await apiFetch('/api/v1/earnings-reports/export-log');

        if (!res.ok) {
          toast('Erro ao buscar dados do relatório (' + res.status + ')', 'danger');
          return;
        }

        const data = await res.json();
        const html = buildReportHtml(data);

        const container = document.createElement('div');
        container.innerHTML = html;
        container.style.position = 'fixed';
        container.style.left = '-9999px';
        document.body.appendChild(container);

        await html2pdf()
          .set({
            margin: 0,
            filename: 'relatorio-emails-earnings.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
          })
          .from(container)
          .save();

        container.remove();
        toast('Relatório PDF exportado com sucesso.');
      } catch (e) {
        toast('Erro ao gerar relatório PDF.', 'danger');
      }
    });
  });

  function showResults(data) {
    resultsArea.classList.remove('d-none');

    const sent = data.results.filter(r => r.status === 'sent').length;
    const notFound = data.results.filter(r => r.status === 'not_found').length;
    resultsSummary.textContent = sent + ' enviado(s)' + (notFound > 0 ? ', ' + notFound + ' não encontrado(s)' : '');

    let html = '<div class="table-soft"><table class="table table-sm mb-0"><thead><tr>' +
      '<th>Player ID</th><th>CPF</th><th>Email</th><th>Status</th></tr></thead><tbody>';

    data.results.forEach(function (r) {
      const statusBadge = r.status === 'sent'
        ? '<span class="badge bg-success-subtle text-success">Enviado</span>'
        : '<span class="badge bg-danger-subtle text-danger">Não encontrado</span>';
      html += '<tr><td>' + escapeHtml(r.player_id) + '</td><td>' + escapeHtml(r.cpf) +
        '</td><td>' + escapeHtml(r.email) + '</td><td>' + statusBadge + '</td></tr>';
    });

    html += '</tbody></table></div>';
    resultsContent.innerHTML = html;
  }
});
</script>
@endpush
