@extends('layouts.app')

@section('title', 'Telegram Bots - Backoffice')
@section('page-title', 'Telegram Bots')

@section('content')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div>
    <h2 class="h5 mb-1">Gerenciar Telegram Bots</h2>
    <div class="text-muted small">Gerenciar bots, configurar webhooks, fluxos e estatísticas</div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" id="btnNew">Novo bot</button>
    <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
  </div>
</div>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-lg-7">
        <label class="form-label">Busca (nome/username)</label>
        <input type="text" class="form-control" id="q" placeholder="Ex: Meu Bot ou @meu_bot">
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label">Status</label>
        <select class="form-select" id="status">
          <option value="">Todos</option>
          <option value="active">active</option>
          <option value="inactive">inactive</option>
          <option value="paused">paused</option>
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
          <th>Username</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 120px;">Webhook</th>
          <th style="width: 380px;" class="text-end">Ações</th>
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTitle">Telegram Bot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <label class="form-label">Nome</label>
            <input class="form-control" id="f_name" placeholder="Ex: Meu Bot">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input class="form-control" id="f_username" placeholder="Ex: meu_bot_123 (sem @)">
            <div class="form-text">5-32 caracteres, apenas letras, números e underscore</div>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Bot Token <span class="text-danger">*</span></label>
            <input class="form-control" id="f_bot_token" placeholder="Obtido do @BotFather">
            <div class="form-text" id="tokenReadOnly" style="display:none;">Somente leitura após criação</div>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Status</label>
            <select class="form-select" id="f_status">
              <option value="active">active</option>
              <option value="inactive">inactive</option>
              <option value="paused">paused</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" id="f_description" rows="2" placeholder="Descrição do bot..."></textarea>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">URL API <span class="text-danger">*</span></label>
            <input class="form-control" id="f_api_url" type="url" placeholder="https://api.dominio.com/validate">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Chave API <span class="text-danger">*</span></label>
            <input class="form-control" id="f_api_key" placeholder="Chave de autenticação">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Portal ID <span class="text-danger">*</span></label>
            <input class="form-control" id="f_portal_id" type="number" placeholder="ID do portal">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">ID do Grupo Telegram</label>
            <input class="form-control" id="f_group_chat_id" placeholder="Ex: -1001234567890">
            <div class="form-text">Obtido com @IDBot no grupo</div>
          </div>

          <div class="col-12">
            <label class="form-label">Link de Convite do Grupo</label>
            <input class="form-control" id="f_group_invite_link" type="url" placeholder="https://t.me/+...">
          </div>

          <div class="col-12">
            <label class="form-label">URL de Cadastro</label>
            <input class="form-control" id="f_register_url" type="url" placeholder="https://betaki.bet.br/register">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="f_debug_mode">
              <label class="form-check-label" for="f_debug_mode">
                Debug Mode (logs detalhados)
              </label>
            </div>
          </div>

          <div class="col-12" id="webhookSection" style="display:none;">
            <hr>
            <h6>Webhook</h6>
            <div class="alert alert-info mb-2">
              URL: <code id="webhookUrl" style="word-break: break-all;"></code>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-outline-primary" id="btnSetupWebhook" type="button">Setup Webhook</button>
              <button class="btn btn-sm btn-outline-info" id="btnTestWebhook" type="button">Testar Webhook</button>
              <button class="btn btn-sm btn-outline-danger" id="btnResetWebhook" type="button">Reset Webhook</button>
            </div>
            <div id="webhookStatus" class="mt-2"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="btnSave">Salvar</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal Detalhes --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes do Bot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="detailsContent">
        <!-- Carregado dinamicamente -->
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
        <button type="button" class="btn btn-danger" id="btnConfirmDelete">Deletar</button>
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
</style>

@endsection

@push('scripts')
<script src="{{ asset('js/telegram-bots.js') }}"></script>
@endpush
