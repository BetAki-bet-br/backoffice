@extends('layouts.app')

@section('title', 'Dashboard - Backoffice')
@section('page-title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Banners</h2>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <div class="h3 mb-0">—</div>
                            <small class="text-muted">Total cadastrados</small>
                        </div>
                        <span class="badge bg-primary-subtle text-dark">Em breve KPIs</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Slots</h2>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <div class="h3 mb-0">—</div>
                            <small class="text-muted">Slots ativos</small>
                        </div>
                        <span class="badge bg-light text-muted">API v1</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Menus</h2>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <div class="h3 mb-0">—</div>
                            <small class="text-muted">Itens configurados</small>
                        </div>
                        <span class="badge bg-light text-muted">Navigation</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Footers</h2>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <div class="h3 mb-0">—</div>
                            <small class="text-muted">Variantes publicadas</small>
                        </div>
                        <span class="badge bg-primary">Novo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body">
            <h2 class="h5 mb-2">Boas-vindas 👋</h2>
            <p class="text-muted mb-1">
                A partir do backoffice você pode editar todas as informações relacionadas a plataforma e consultar dados.
            </p>
            <p class="text-muted mb-0">
                Em breve exibiremos estatísticas importantes para gestão no dashboard.
            </p>
        </div>
    </div>
@endsection