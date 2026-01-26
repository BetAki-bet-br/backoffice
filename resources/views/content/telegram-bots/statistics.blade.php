@extends('layouts.app')

@section('title', 'Bot Statistics - Backoffice')
@section('page-title', 'Estatísticas')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Estatísticas do Bot</h2>
    <div class="text-muted small">Analytics e relatórios de desempenho</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-secondary" id="btnBack" onclick="window.history.back()">Voltar</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
    <button class="btn btn-outline-success" id="btnExport">Exportar CSV</button>
  </div>
</div>

<div class="alert alert-info" id="botInfo" style="display:none;">
  <strong id="botName"></strong> (@<span id="botUsername"></span>)
</div>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card card-soft">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-lg-3">
            <label class="form-label">Período (dias)</label>
            <select class="form-select" id="filterDays">
              <option value="7">Últimos 7 dias</option>
              <option value="14">Últimos 14 dias</option>
              <option value="30" selected>Últimos 30 dias</option>
              <option value="90">Últimos 90 dias</option>
            </select>
          </div>

          <div class="col-12 col-lg-9 d-flex gap-2">
            <button class="btn btn-secondary w-100" id="btnApplyFilter">Aplicar filtro</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Mensagens Enviadas</div>
            <h3 class="mb-0 mt-2" id="statMessagesSent">—</h3>
          </div>
          <div class="text-primary" style="font-size: 1.5rem;">📤</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Mensagens Recebidas</div>
            <h3 class="mb-0 mt-2" id="statMessagesReceived">—</h3>
          </div>
          <div class="text-info" style="font-size: 1.5rem;">📥</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Usuários Novos</div>
            <h3 class="mb-0 mt-2" id="statUsersNew">—</h3>
          </div>
          <div class="text-success" style="font-size: 1.5rem;">👥</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Taxa de Sucesso</div>
            <h3 class="mb-0 mt-2" id="statSuccessRate">—</h3>
          </div>
          <div class="text-warning" style="font-size: 1.5rem;">✅</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Validações</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-6">
            <div class="text-muted small">Sucesso</div>
            <h4 class="mb-0" id="statValidationSuccess">—</h4>
          </div>
          <div class="col-6">
            <div class="text-muted small">Falhas</div>
            <h4 class="mb-0" id="statValidationFailed">—</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Resumo Geral</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-6">
            <div class="text-muted small">Total de Mensagens</div>
            <h4 class="mb-0" id="statTotalMessages">—</h4>
          </div>
          <div class="col-6">
            <div class="text-muted small">Última Atualização</div>
            <p class="mb-0 small" id="statLastUpdate">—</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Atividade por Período</h6>
      </div>
      <div class="card-body">
        <canvas id="activityChart" style="max-height: 300px;"></canvas>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">Distribuição</h6>
      </div>
      <div class="card-body">
        <canvas id="distributionChart" style="max-height: 300px;"></canvas>
      </div>
    </div>
  </div>
</div>

{{-- Usuários Validados --}}
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Usuários Validados (últimos 50)</h6>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr class="bg-light">
              <th style="width: 80px;">ID Telegram</th>
              <th>Nome</th>
              <th>Email</th>
              <th style="width: 120px;">Status</th>
              <th style="width: 140px;">Data</th>
            </tr>
          </thead>
          <tbody id="validatedUsersTable">
            <tr><td colspan="5" class="text-muted p-4 text-center">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Usuários com Falha --}}
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Usuários com Falha (últimos 50)</h6>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr class="bg-light">
              <th style="width: 80px;">ID Telegram</th>
              <th>Nome</th>
              <th>Email</th>
              <th style="width: 120px;">Status</th>
              <th style="width: 140px;">Data</th>
            </tr>
          </thead>
          <tbody id="failedUsersTable">
            <tr><td colspan="5" class="text-muted p-4 text-center">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Log de Mensagens --}}
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Log de Mensagens (últimas 100)</h6>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
          <thead>
            <tr class="bg-light">
              <th style="width: 80px;">ID</th>
              <th style="width: 100px;">Direção</th>
              <th>Conteúdo</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 160px;">Data</th>
            </tr>
          </thead>
          <tbody id="messagesLogTable">
            <tr><td colspan="5" class="text-muted p-4 text-center">Carregando…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
  .table-soft {
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
  }

  .card-soft {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
  }

  .status-badge {
    font-size: 0.8rem;
    padding: 0.35rem 0.65rem;
  }
</style>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('js/bot-statistics.js') }}"></script>
@endpush
