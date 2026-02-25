@extends('layouts.app')

@section('title', 'Lobbies - Backoffice')
@section('page-title', 'Gerenciar Lobbies')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Configuração de Lobbies</h2>
            <div class="text-muted small">Defina a ordem e o conteúdo das seções para cada vertical.</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnSave">Salvar Alterações</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Vertical</label>
                    <select class="form-select" id="verticalSelector">
                        <option value="slots">Slots (Casino)</option>
                        <option value="live">Live Casino</option>
                    </select>
                </div>
                <div class="col-12 col-lg-8 text-end">
                    <button class="btn btn-outline-secondary" id="btnReload">Recarregar Configuração</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="h6 mb-0">Seções do Lobby</h5>
                <div class="d-flex gap-2">
                    {{-- <button class="btn btn-sm btn-outline-secondary" id="btnLoadAllCategories">Carregar todas as categorias</button> --}}
                    <button class="btn btn-sm btn-primary" id="btnAddSection">+ Adicionar Seção</button>
                </div>
            </div>

            <div class="list-group list-group-flush border rounded bg-white" id="sectionsList">
                <div class="p-4 text-center text-muted">Carregando...</div>
            </div>

            <div class="mt-2 text-muted small">
                Arraste os itens para reordenar. Clique em "Salvar Alterações" para persistir.
            </div>
        </div>
    </div>

    {{-- Modal Add/Edit Section --}}
    <div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectionModalTitle">Configurar Seção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Seção</label>
                        <select class="form-select" id="secType">
                            <option value="game-list">Lista de Jogos (Categoria)</option>
                            <option value="top-10-list">Top 10 / Ranking</option>
                            <option value="mais-premiados">Mais Premiados</option>
                            <option value="winners-list">Últimos Vencedores</option>
                            {{-- <option value="banner-carousel">Carrossel de Banners</option> --}}
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título (Override)</label>
                        <input type="text" class="form-control" id="secTitle" placeholder="Ex: Populares, Novos...">
                        <div class="form-text">Deixe vazio para usar o nome original da fonte.</div>
                    </div>

                    {{-- Resource Selectors --}}
                    <div class="mb-3 d-none" id="groupCategory">
                        <label class="form-label">Categoria Fonte</label>
                        <select class="form-select" id="secCategoryId"></select>
                        <div class="form-text">Categorias com status 'active' e jogos vinculados.</div>
                    </div>

                    <div class="mb-3 d-none" id="groupTopList">
                        <label class="form-label">Top List Fonte</label>
                        <select class="form-select" id="secTopListId"></select>
                    </div>

                    <div class="mb-3 d-none" id="groupAwarded">
                        <label class="form-label">Batch de Premiados</label>
                        <select class="form-select" id="secAwardedBatchId"></select>
                    </div>

                    <div class="mb-3 d-none" id="groupWinners">
                        <label class="form-label">Batch de Vencedores</label>
                        <select class="form-select" id="secWinnersBatchId"></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade de Itens (Display Count)</label>
                        <input type="number" class="form-control" id="secDisplayCount" placeholder="Ex: 10">
                        <div class="form-text">Vazio = padrão do sistema.</div>
                    </div>

                    <input type="hidden" id="secIndex"> {{-- To track if editing existing --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmSection">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            const verticalSelector = document.getElementById('verticalSelector');
            const sectionsList = document.getElementById('sectionsList');
            const sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));

            // Selectors
            const secType = document.getElementById('secType');
            const secCategoryId = document.getElementById('secCategoryId');
            const secTopListId = document.getElementById('secTopListId');
            const secAwardedBatchId = document.getElementById('secAwardedBatchId');
            const secWinnersBatchId = document.getElementById('secWinnersBatchId');

            // State
            let currentSections = []; // Array of section objects
            let loadedResources = {
                categories: [],
                topLists: [],
                awardedBatches: [],
                winnersBatches: []
            };

            function badge(status) {
                const s = (status || '').toLowerCase();
                const map = {
                    active: 'bg-success-subtle text-success',
                    inactive: 'bg-secondary-subtle text-secondary',
                    slots: 'bg-info-subtle text-info-emphasis',
                    live: 'bg-warning-subtle text-warning-emphasis',

                    'game-list': 'bg-primary-subtle text-primary',
                    'top-10-list': 'bg-info-subtle text-info-emphasis',
                    'mais-premiados': 'bg-success-subtle text-success-emphasis',
                    'winners-list': 'bg-warning-subtle text-warning-emphasis',
                };
                const cls = map[s] || 'bg-light text-muted';
                return `<span class="badge ${cls}">${status || '—'}</span>`;
            }

            // --- Init ---
            initSortable();
            loadResources(); // Load resource lists once (or per vertical change if needed)

            // --- Event Listeners ---
            verticalSelector.addEventListener('change', () => loadConfig());
            document.getElementById('btnReload').addEventListener('click', () => loadConfig());
            document.getElementById('btnSave').addEventListener('click', saveConfig);
            document.getElementById('btnAddSection').addEventListener('click', () => openSectionModal());
            document.getElementById('btnConfirmSection').addEventListener('click', confirmSectionModal);
            // document.getElementById('btnLoadAllCategories').addEventListener('click', loadAllCategories);

            secType.addEventListener('change', updateModalFields);

            // --- Functions ---

            function loadAllCategories() {
                if (!confirm(
                        'Isso substituirá a configuração atual pelas categorias de jogo da vertical selecionada. Deseja continuar?'
                    )) return;

                const vertical = verticalSelector.value;
                const gameListCategories = loadedResources.categories.filter(c => {
                    const catsVerticals = c.verticals || (c.vertical ? [c.vertical] : []);
                    return catsVerticals.includes(vertical) && (c.type === 'game-list' || c.type === null);
                });

                currentSections = gameListCategories.map(c => ({
                    type: 'game-list',
                    title: c.name,
                    categoryId: c.id,
                    metadata: {}
                }));

                renderSections();
                toast(`${currentSections.length} categorias carregadas. Ordene e salve.`);
            }

            function moveSection(index, direction) {
                const newIndex = index + direction;
                if (newIndex < 0 || newIndex >= currentSections.length) return;

                const movedItem = currentSections.splice(index, 1)[0];
                currentSections.splice(newIndex, 0, movedItem);
                renderSections();
            }

            function initSortable() {
                new Sortable(sectionsList, {
                    animation: 150,
                    handle: '.handle',
                    ghostClass: 'bg-light',
                    onEnd: function(evt) {

                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;

                        if (oldIndex === newIndex) return;

                        const movedItem = currentSections.splice(oldIndex, 1)[0];
                        currentSections.splice(newIndex, 0, movedItem);


                        renderSections();
                    }
                });
            }

            async function loadResources() {
                try {

                    const resCat = await apiFetch('/api/v1/categories?status=active&per_page=9999');
                    if (resCat.ok) {
                        const data = await resCat.json();

                        console.log('Categorias carregadas:', data.data);
                        loadedResources.categories = data.data || [];
                    }

                    // Fetch TopLists
                    const resTL = await apiFetch('/api/v1/top-lists?status=published');
                    if (resTL.ok) {
                        const data = await resTL.json();
                        loadedResources.topLists = data.data || [];
                    }

                    // Fetch Awards
                    const resAw = await apiFetch('/api/v1/awards/batches?status=published');
                    if (resAw.ok) {
                        const data = await resAw.json();
                        loadedResources.awardedBatches = data.data || [];
                    }

                    // Fetch Winners
                    const resWin = await apiFetch('/api/v1/winners/batches?status=published');
                    if (resWin.ok) {
                        const data = await resWin.json();
                        loadedResources.winnersBatches = data.data || [];
                    }

                    // After loading resources, load the config
                    loadConfig();

                } catch (e) {
                    console.error(e);
                    toast('Erro ao carregar recursos auxiliares.', 'danger');
                }
            }

            async function loadConfig() {
                const vertical = verticalSelector.value;
                sectionsList.innerHTML = '<div class="p-4 text-center text-muted">Carregando configuração...</div>';

                try {
                    const res = await apiFetch(`/api/v1/lobbies/${vertical}/config`);
                    if (!res.ok) {
                        sectionsList.innerHTML =
                            '<div class="p-4 text-center text-danger">Erro ao carregar configuração.</div>';
                        await toastApiError(res, 'carregar configuração');
                        return;
                    }

                    const data = await res.json();
                    currentSections = data.sections || [];

                    renderSections();

                } catch (e) {
                    console.error(e);
                    sectionsList.innerHTML =
                        '<div class="p-4 text-center text-danger">Erro ao carregar configuração.</div>';
                    toast('Erro ao carregar configuração.', 'danger');
                }
            }

            function renderSections() {
                if (!currentSections.length) {
                    sectionsList.innerHTML =
                        '<div class="p-4 text-center text-muted">Nenhuma seção configurada. Adicione uma seção ou o sistema usará o padrão (todas categorias).</div>';
                    return;
                }

                sectionsList.innerHTML = '';
                currentSections.forEach((sec, idx) => {
                    const el = document.createElement('div');
                    el.className =
                        'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                    let label = badge(sec.type);
                    let title = sec.title || '—';
                    let detail = '';
                    let verticalBadge = '';

                    if (sec.type === 'game-list') {
                        const cat = loadedResources.categories.find(c => c.id == (sec.categoryId || sec.category_id));
                        if (cat) {
                            detail = `Fonte: ${cat.name}`;
                            const verticals = (cat.verticals || (cat.vertical ? [cat.vertical] : []));
                            verticalBadge = verticals.map(v => badge(v)).join(' ');
                        } else {
                            detail = `CatID: ${sec.categoryId}`;
                        }
                    } else if (sec.type === 'top-10-list') {
                        const tl = loadedResources.topLists.find(t => t.id == (sec.topListId || sec.top_list_id));
                        if (tl) {
                            detail = `Fonte: ${tl.title}`;
                            if (tl.vertical) verticalBadge = badge(tl.vertical);
                        } else {
                            detail = `TL ID: ${sec.topListId}`;
                        }
                    } else if (sec.type === 'mais-premiados') {
                        const aw = loadedResources.awardedBatches.find(b => b.id == (sec.awardedBatchId || sec
                            .batchId));
                        if (aw) {
                            detail = `Fonte: ${aw.title}`;
                            if (aw.vertical) verticalBadge = badge(aw.vertical);
                        } else {
                            detail = `Batch ID: ${sec.awardedBatchId || sec.batchId}`;
                        }
                    } else if (sec.type === 'winners-list') {
                        const wn = loadedResources.winnersBatches.find(b => b.id == (sec.winnersBatchId || sec
                            .batchId));
                        if (wn) {
                            detail = `Fonte: ${wn.title}`;
                            if (wn.vertical) verticalBadge = badge(wn.vertical);
                        } else {
                            detail = `Batch ID: ${sec.winnersBatchId || sec.batchId}`;
                        }
                    }

                    el.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="handle text-muted px-2" style="cursor: grab; font-size: 1.5rem; line-height: 1;">≡</div>
                    <div class="d-flex align-items-center gap-1 me-2">
                        <button class="btn btn-sm btn-light border py-0 px-1 btn-move-up" type="button" title="Mover para cima">▲</button>
                        <span class="badge bg-light text-dark border" style="min-width: 32px;" data-pos-display>${idx + 1}</span>
                        <button class="btn btn-sm btn-light border py-0 px-1 btn-move-down" type="button" title="Mover para baixo">▼</button>
                    </div>
                    <div>
                        <div class="fw-semibold">${label} ${title}</div>
                        <div class="small text-muted">${detail} ${verticalBadge}</div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary btn-edit">Editar</button>
                    <button class="btn btn-sm btn-outline-danger btn-remove">×</button>
                </div>
            `;

                    // Event Handlers
                    el.querySelector('.btn-edit').addEventListener('click', () => openSectionModal(idx));
                    el.querySelector('.btn-remove').addEventListener('click', () => removeSection(idx));
                    el.querySelector('.btn-move-up').addEventListener('click', (e) => {
                        e.stopPropagation();
                        moveSection(idx, -1);
                    });
                    el.querySelector('.btn-move-down').addEventListener('click', (e) => {
                        e.stopPropagation();
                        moveSection(idx, 1);
                    });

                    sectionsList.appendChild(el);
                });
            }

            function openSectionModal(index = null) {
                // Populate Selects based on Vertical
                const vertical = verticalSelector.value;

                // Categoria Fonte options are now populated dynamically by updateModalFields

                // Populate TopLists
                secTopListId.innerHTML = '<option value="">Selecione...</option>';
                loadedResources.topLists
                    .filter(t => t.vertical === vertical)
                    .forEach(t => {
                        secTopListId.innerHTML += `<option value="${t.id}">${t.title}</option>`;
                    });

                // Populate Batches (Assuming they have vertical field)
                secAwardedBatchId.innerHTML = '<option value="">Selecione...</option>';
                loadedResources.awardedBatches
                    .filter(b => b.vertical === vertical)
                    .forEach(b => {
                        secAwardedBatchId.innerHTML += `<option value="${b.id}">${b.title}</option>`;
                    });

                secWinnersBatchId.innerHTML = '<option value="">Selecione...</option>';
                loadedResources.winnersBatches
                    .filter(b => b.vertical === vertical)
                    .forEach(b => {
                        secWinnersBatchId.innerHTML += `<option value="${b.id}">${b.title}</option>`;
                    });


                // Load Data or Reset
                if (index !== null && currentSections[index]) {
                    const sec = currentSections[index];
                    document.getElementById('secIndex').value = index;
                    document.getElementById('sectionModalTitle').textContent = 'Editar Seção';

                    secType.value = sec.type || 'game-list';
                    document.getElementById('secTitle').value = sec.title || '';
                    document.getElementById('secDisplayCount').value = (sec.metadata && sec.metadata.displayCount) ? sec
                        .metadata.displayCount : '';

                    // Set IDs
                    secCategoryId.value = sec.categoryId || sec.category_id || '';
                    secTopListId.value = sec.topListId || sec.top_list_id || '';
                    secAwardedBatchId.value = sec.awardedBatchId || sec.batchId || '';
                    secWinnersBatchId.value = sec.winnersBatchId || sec.batchId || '';

                } else {
                    document.getElementById('secIndex').value = '';
                    document.getElementById('sectionModalTitle').textContent = 'Nova Seção';
                    secType.value = 'game-list';
                    document.getElementById('secTitle').value = '';
                    document.getElementById('secDisplayCount').value = '';
                    secCategoryId.value = '';
                    secTopListId.value = '';
                    secAwardedBatchId.value = '';
                    secWinnersBatchId.value = '';
                }

                updateModalFields();
                sectionModal.show();
            }

            function updateModalFields() {
                const type = secType.value;

                document.getElementById('groupCategory').classList.add('d-none');
                document.getElementById('groupTopList').classList.add('d-none');
                document.getElementById('groupAwarded').classList.add('d-none');
                document.getElementById('groupWinners').classList.add('d-none');

                if (type === 'game-list') {
                    document.getElementById('groupCategory').classList.remove('d-none');

                    secCategoryId.innerHTML = '<option value="">Selecione...</option>';
                    loadedResources.categories
                        .forEach(c => {
                            secCategoryId.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                        });
                }
                if (type === 'top-10-list') document.getElementById('groupTopList').classList.remove('d-none');
                if (type === 'mais-premiados') document.getElementById('groupAwarded').classList.remove('d-none');
                if (type === 'winners-list') document.getElementById('groupWinners').classList.remove('d-none');
            }

            function confirmSectionModal() {
                const indexStr = document.getElementById('secIndex').value;
                const isEdit = indexStr !== '';

                const type = secType.value;
                const title = document.getElementById('secTitle').value.trim();
                const displayCount = document.getElementById('secDisplayCount').value;

                let newSec = {
                    type: type,
                    title: title || null,
                    metadata: {}
                };

                if (displayCount) {
                    newSec.metadata.displayCount = parseInt(displayCount);
                }

                // Extract ID based on type
                if (type === 'game-list') {
                    const val = secCategoryId.value;
                    if (!val) return toast('Selecione uma categoria.', 'warning');
                    newSec.categoryId = parseInt(val);
                } else if (type === 'top-10-list') {
                    const val = secTopListId.value;
                    if (!val) return toast('Selecione uma Top List.', 'warning');
                    newSec.topListId = parseInt(val);
                } else if (type === 'mais-premiados') {
                    const val = secAwardedBatchId.value;
                    if (!val) return toast('Selecione um Batch.', 'warning');
                    newSec.awardedBatchId = parseInt(val);
                } else if (type === 'winners-list') {
                    const val = secWinnersBatchId.value;
                    // winners doesn't strictly require ID if it's dynamic/latest, but here we enforce selection if we have batches
                    if (val) newSec.winnersBatchId = parseInt(val);
                }

                if (isEdit) {
                    currentSections[parseInt(indexStr)] = newSec;
                } else {
                    currentSections.push(newSec);
                }

                sectionModal.hide();
                renderSections();
            }

            function removeSection(index) {
                if (!confirm('Remover esta seção?')) return;
                currentSections.splice(index, 1);
                renderSections();
            }

            async function saveConfig() {
                const vertical = verticalSelector.value;
                const btn = document.getElementById('btnSave');
                const originalText = btn.innerText;
                btn.innerText = 'Salvando...';
                btn.disabled = true;

                try {
                    // Re-index order property based on array index just in case, though the array order defines it
                    const sectionsWithOrder = currentSections.map((s, i) => ({
                        ...s,
                        order: i
                    }));

                    const res = await apiFetch(`/api/v1/lobbies/${vertical}/config`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            sections: sectionsWithOrder
                        })
                    });

                    if (!res.ok) {
                        await toastApiError(res, 'salvar configuração');
                        return;
                    }

                    toast('Configuração salva com sucesso!');
                } catch (e) {
                    console.error(e);
                    toast('Erro ao salvar configuração.', 'danger');
                } finally {
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            }
        </script>
    @endpush
@endsection
