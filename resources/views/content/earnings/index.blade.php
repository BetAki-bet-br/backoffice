@extends('layouts.app')

@section('title', 'Relatórios de Ganhos - Backoffice')
@section('page-title', 'Relatórios de Ganhos')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Envio de Relatórios Anuais</h2>
    <div class="text-muted small">Envie relatórios de ganhos individuais por email para jogadores</div>
  </div>
</div>

{{-- Upload do arquivo de earnings --}}
<div class="card card-soft mb-4">
  <div class="card-body">
    <h6 class="card-title mb-3">Arquivo de Earnings</h6>
    <div id="fileStatus" class="mb-3">
      <span class="text-muted">Verificando...</span>
    </div>
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-6">
        <label class="form-label" for="earningsFile">Enviar novo arquivo (.xlsx)</label>
        <input type="file" class="form-control" id="earningsFile" accept=".xlsx">
      </div>
      <div class="col-12 col-lg-3">
        <button class="btn btn-outline-secondary w-100" id="btnUpload" disabled>Enviar arquivo</button>
      </div>
    </div>
  </div>
</div>

{{-- Formulário de envio --}}
<div class="card card-soft mb-4">
  <div class="card-body">
    <h6 class="card-title mb-3">Enviar Relatórios</h6>

    <div class="row g-2 mb-3">
      <div class="col-6 col-lg-2">
        <label class="form-label" for="reportYear">Ano</label>
        <select class="form-select" id="reportYear">
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
              <th>CPF</th>
              <th>Player ID</th>
              <th>Email</th>
              <th style="width: 60px;"></th>
            </tr>
          </thead>
          <tbody id="playersBody">
            {{-- Dynamic rows inserted here --}}
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" id="btnAddPlayer" type="button">+ Adicionar jogador</button>
      <button class="btn btn-primary" id="btnSend" type="button">Enviar Relatórios</button>
    </div>

    {{-- Resultados --}}
    <div id="resultsArea" class="mt-3 d-none">
      <h6>Resultado do envio</h6>
      <div id="resultsContent"></div>
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
  const btnUpload = document.getElementById('btnUpload');
  const fileInput = document.getElementById('earningsFile');
  const fileStatus = document.getElementById('fileStatus');
  const resultsArea = document.getElementById('resultsArea');
  const resultsContent = document.getElementById('resultsContent');

  let rowCounter = 0;

  // --- File status ---
  async function checkFileStatus() {
    try {
      const res = await apiFetch('/api/v1/earnings-reports/status');
      if (!res.ok) { fileStatus.innerHTML = '<span class="text-danger">Erro ao verificar arquivo.</span>'; return; }
      const data = await res.json();
      if (data.uploaded) {
        const dt = new Date(data.uploaded_at).toLocaleString('pt-BR');
        const sizeMb = (data.size / (1024 * 1024)).toFixed(1);
        fileStatus.innerHTML = '<span class="badge bg-success-subtle text-success me-2">Arquivo presente</span>' +
          '<span class="text-muted small">' + escapeHtml(data.file) + ' — ' + sizeMb + ' MB — Enviado em ' + dt + '</span>';
      } else {
        fileStatus.innerHTML = '<span class="badge bg-warning-subtle text-warning">Nenhum arquivo enviado</span>' +
          '<span class="text-muted small ms-2">Faça o upload de um arquivo .xlsx de earnings.</span>';
      }
    } catch (e) {
      fileStatus.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
    }
  }
  checkFileStatus();

  // --- File upload ---
  fileInput.addEventListener('change', function () {
    btnUpload.disabled = !fileInput.files.length;
  });

  btnUpload.addEventListener('click', function () {
    if (!fileInput.files.length) return;
    const fd = new FormData();
    fd.append('file', fileInput.files[0]);

    withLoading(btnUpload, async () => {
      const res = await apiFetch('/api/v1/earnings-reports/upload', { method: 'POST', body: fd });
      if (res.ok) {
        toast('Arquivo enviado com sucesso!');
        fileInput.value = '';
        btnUpload.disabled = true;
        checkFileStatus();
      } else {
        await toastApiError(res, 'enviar arquivo');
      }
    });
  });

  // --- Player rows ---
  function addPlayerRow(cpf, playerId, email) {
    rowCounter++;
    const id = rowCounter;
    const tr = document.createElement('tr');
    tr.id = 'row-' + id;
    tr.innerHTML = `
      <td><input type="text" class="form-control form-control-sm cpf-input" placeholder="000.000.000-00" data-row="${id}" maxlength="14"></td>
      <td><input type="text" class="form-control form-control-sm player-id-input" placeholder="Ex: 10310001" data-row="${id}"></td>
      <td><input type="email" class="form-control form-control-sm email-input" placeholder="email@exemplo.com" data-row="${id}"></td>
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
      tr.remove();
    });
  }

  // Start with one row
  addPlayerRow();

  btnAdd.addEventListener('click', function () {
    addPlayerRow();
  });

  // --- Send ---
  btnSend.addEventListener('click', function () {
    const rows = playersBody.querySelectorAll('tr');
    const players = [];

    let valid = true;
    rows.forEach(function (tr) {
      const cpf = tr.querySelector('.cpf-input').value.trim();
      const playerId = tr.querySelector('.player-id-input').value.trim();
      const email = tr.querySelector('.email-input').value.trim();

      if (!cpf || !playerId || !email) {
        valid = false;
        return;
      }

      players.push({ cpf: cpf, player_id: playerId, email: email });
    });

    if (!valid || players.length === 0) {
      toast('Preencha todos os campos (CPF, Player ID e Email) de cada linha.', 'warning');
      return;
    }

    const year = parseInt(document.getElementById('reportYear').value);

    withLoading(btnSend, async () => {
      const res = await apiFetch('/api/v1/earnings-reports/send', {
        method: 'POST',
        body: JSON.stringify({ year: year, players: players }),
      });

      if (res.ok) {
        const data = await res.json();
        showResults(data);
        toast(data.message);
      } else {
        await toastApiError(res, 'enviar relatórios');
      }
    });
  });

  function showResults(data) {
    resultsArea.classList.remove('d-none');
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
