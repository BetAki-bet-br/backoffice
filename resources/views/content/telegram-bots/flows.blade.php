@extends('layouts.app')

@section('title', 'Bot Flows - Backoffice')
@section('page-title', 'Bot Flows')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Fluxos de Mensagens</h2>
    <div class="text-muted small">Criar e gerenciar fluxos de automação de bots telegram</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-secondary" id="btnBack" onclick="window.history.back()">Voltar</button>
    <button class="btn btn-primary" id="btnNew">Novo fluxo</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="alert alert-info" id="botInfo" style="display:none;">
  <strong id="botName"></strong> (@<span id="botUsername"></span>)
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-7">
        <label class="form-label">Busca</label>
        <input type="text" class="form-control" id="q" placeholder="Ex: Fluxo de boas-vindas">
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Status</label>
        <select class="form-select" id="status">
          <option value="">Todos</option>
          <option value="draft">draft</option>
          <option value="active">active</option>
          <option value="archived">archived</option>
        </select>
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
          <th>Nome</th>
          <th style="width: 100px;">Versão</th>
          <th style="width: 100px;">Status</th>
          <th style="width: 80px;">Padrão</th>
          <th style="width: 360px;" class="text-end">Ações</th>
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
        <h5 class="modal-title" id="editTitle">Fluxo de Mensagens</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-8">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input class="form-control" id="f_name" placeholder="Ex: Fluxo de Boas-vindas">
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="draft">draft</option>
              <option value="active">active</option>
              <option value="archived">archived</option>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">Versão</label>
            <input class="form-control" id="f_version" type="number" readonly>
          </div>

          <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" id="f_description" rows="2" placeholder="Descrição do fluxo..."></textarea>
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="f_is_default">
              <label class="form-check-label" for="f_is_default">
                Usar como fluxo padrão (ao comando /start)
              </label>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6>Passos do Fluxo</h6>
            <button class="btn btn-sm btn-outline-primary" id="btnAddStep" type="button">+ Adicionar Step</button>
          </div>

          <div id="flowStepsContainer" class="border rounded p-3 bg-light">
            <!-- Steps carregados dinamicamente -->
            <div class="text-muted text-center py-4">Nenhum step adicionado. Clique em "+ Adicionar Step"</div>
          </div>
        </div>

        {{-- Template de Step --}}
        <template id="stepTemplate">
          <div class="card mb-2 step-card" data-step-id="">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <strong class="step-title">Step #</strong>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary moveUpBtn" type="button" title="Mover para cima">↑</button>
                <button class="btn btn-sm btn-outline-secondary moveDownBtn" type="button" title="Mover para baixo">↓</button>
                <button class="btn btn-sm btn-outline-danger removeStepBtn" type="button">Remover</button>
              </div>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-12 col-lg-3">
                  <label class="form-label">Tipo</label>
                  <select class="form-select stepType" required>
                    <option value="message">message</option>
                    <option value="buttons">buttons</option>
                    <option value="input">input</option>
                    <option value="validation">validation</option>
                    <option value="condition">condition</option>
                    <option value="action">action</option>
                  </select>
                </div>

                <div class="col-12 stepDataContainer">
                  <!-- Conteúdo dinâmico por tipo -->
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="btnSave">Salvar Fluxo</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal Preview --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Preview do Fluxo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="previewContent" style="max-height: 500px; overflow-y: auto;">
        <!-- Preview gerado dinamicamente -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de Confirmação --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar ação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmMessage"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmDelete">Confirmar</button>
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

  .table-soft table {
    background: white;
  }

  .card-soft {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
  }

  .status-badge {
    font-size: 0.8rem;
    padding: 0.35rem 0.65rem;
  }

  .action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
  }

  .step-card {
    border-left: 4px solid #0d6efd;
  }

  .step-card.message {
    border-left-color: #198754;
  }

  .step-card.buttons {
    border-left-color: #0dcaf0;
  }

  .step-card.input {
    border-left-color: #ffc107;
  }

  .step-card.validation {
    border-left-color: #fd7e14;
  }

  .step-card.condition {
    border-left-color: #6f42c1;
  }

  .step-card.action {
    border-left-color: #e83e8c;
  }
</style>

@endsection

@push('scripts')
<script src="{{ asset('js/bot-flows.js') }}"></script>
@endpush
