@extends('layouts.app')

@section('title', 'Carroséis - Backoffice')
@section('page-title', 'Carroséis')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">Gerenciar Carroséis</h2>
            <div class="text-muted small">Agrupamentos de slides para a home.</div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" id="btnNew">Novo Carrossel</button>
            <button class="btn btn-outline-secondary" id="btnReload">Atualizar</button>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-9">
                    <label class="form-label">Busca</label>
                    <input type="text" class="form-control" id="q" placeholder="Buscar por nome ou slug...">
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
                        <th>Slug</th>
                        <th>Slides</th>
                        <th style="width: 200px;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                    <tr>
                        <td colspan="5" class="text-muted p-4">Carregando…</td>
                    </tr>
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
                    <h5 class="modal-title" id="editTitle">Carrossel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <h6 class="mb-3">Dados do Carrossel</h6>
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Nome</label>
                            <input class="form-control" id="f_name" placeholder="Ex: Carrossel da Home">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Slug</label>
                            <input class="form-control" id="f_slug" placeholder="ex: home-top-carousel">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Slides do Carrossel</h6>
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="addSlide">Adicionar
                            Slide</button>
                    </div>
                    <div id="slidesWrap" class="d-grid gap-3">
                        {{-- Slides will be injected here by JS --}}
                    </div>

                    <div class="alert alert-danger d-none mt-3 small" id="saveError"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="btnSave">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Template for a single slide form --}}
    <template id="slideTemplate">
        <div class="card card-soft slide-form" data-slide-id="">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="handle" style="cursor: grab; font-size: 1.2rem; color: #6c757d;">≡</div>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-action="remove-slide">Remover</button>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Link (href)</label>
                        <input type="text" class="form-control" data-field="href" placeholder="https://...">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Alt Text</label>
                        <input type="text" class="form-control" data-field="alt"
                            placeholder="Texto alternativo da imagem">
                    </div>

                    <div class="col-12 col-lg-6" style="display: none;">
                        <label class="form-label">Image URL</label>
                        <input type="text" class="form-control" data-field="image_url"
                            placeholder="https://... ou envie um arquivo abaixo">
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label">Imagem do Slide</label>
                        <input type="file" class="form-control" data-field="image_file" accept="image/*">
                        <div class="form-text">Envie uma imagem para o slide.</div>
                        <div class="mt-2" data-preview-wrapper>
                            <img data-preview-img class="img-thumbnail"
                                style="max-width: 200px; max-height: 200px; display: none;">
                            <button type="button" class="btn btn-sm btn-danger ms-2" data-action="remove-image"
                                style="display: none;">Remover Imagem</button>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label">Duração</label>
                        <input type="text" class="form-control" data-field="duration"
                            placeholder="Ex: 5s, 3000 ms, 5 segundos por padrão.">
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label">Ordem</label>
                        <input type="number" class="form-control" data-field="order" value="0" readonly>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            const tbody = document.getElementById('tbody');
            const info = document.getElementById('paginationInfo');

            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const editTitle = document.getElementById('editTitle');
            const saveError = document.getElementById('saveError');

            // pagination state (standard laravel paginate)
            let currentPage = 1;
            let lastPage = 1;

            // cache items by id
            const cacheById = new Map();
            let editingId = null;
            let sortableInstance = null;

            function render(rows) {
                if (!rows.length) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Nenhum registro.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(item => {
                    return `
        <tr>
          <td class="text-muted">#${item.id}</td>
          <td>
            <div class="fw-semibold">${item.name || '—'}</div>
          </td>
          <td><code>${item.slug || '—'}</code></td>
          <td class="text-muted">${item.slides_count || 0}</td>
          <td class="text-end">
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${item.id}">Editar</button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">Excluir</button>
            </div>
          </td>
        </tr>
      `;
                }).join('');
            }

            function fillForm(item) {
                document.getElementById('f_name').value = item.name || '';
                document.getElementById('f_slug').value = item.slug || '';

                // Clear and fill slides
                const slidesWrap = document.getElementById('slidesWrap');
                slidesWrap.innerHTML = '';
                if (item.slides && item.slides.length) {
                    item.slides.forEach(addSlideBlock);
                }
            }

            function buildPayload() {
                const formData = new FormData();
                formData.append('name', document.getElementById('f_name').value.trim() || '');
                formData.append('slug', document.getElementById('f_slug').value.trim() || '');

                document.querySelectorAll('.slide-form').forEach((form, index) => {
                    const id = form.dataset.slideId;
                    if (id) {
                        formData.append(`slides[${index}][id]`, id);
                    }
                    formData.append(`slides[${index}][href]`, form.querySelector('[data-field=href]').value);
                    formData.append(`slides[${index}][alt]`, form.querySelector('[data-field=alt]').value);
                    formData.append(`slides[${index}][duration]`, form.querySelector('[data-field=duration]').value);
                    formData.append(`slides[${index}][order]`, form.querySelector('[data-field=order]').value);

                    // Handle image url and file
                    const imageUrlInput = form.querySelector('[data-field=image_url]');
                    const imageFileInput = form.querySelector('[data-field=image_file]');

                    if (imageFileInput && imageFileInput.files[0]) {
                        formData.append(`slides[${index}][image]`, imageFileInput.files[0]);
                    } else if (imageUrlInput) {
                        formData.append(`slides[${index}][image_url]`, imageUrlInput.value);
                    }
                });

                return formData;
            }

            function updateSlideOrderNumbers() {
                const slideForms = document.querySelectorAll('#slidesWrap .slide-form');
                slideForms.forEach((form, index) => {
                    const orderInput = form.querySelector('[data-field=order]');
                    if (orderInput) {
                        orderInput.value = index;
                    }
                });
            }

            function addSlideBlock(initial = {}) {
                const template = document.getElementById('slideTemplate');
                const clone = template.content.cloneNode(true);
                const slideForm = clone.querySelector('.slide-form');

                const previewImg = slideForm.querySelector('[data-preview-img]');
                const removeBtn = slideForm.querySelector('[data-action=remove-image]');
                const imageUrlInput = slideForm.querySelector('[data-field=image_url]');
                const imageFileInput = slideForm.querySelector('[data-field=image_file]');

                if (initial.id) slideForm.dataset.slideId = initial.id;
                slideForm.querySelector('[data-field=href]').value = initial.href || '';
                slideForm.querySelector('[data-field=alt]').value = initial.alt || '';
                slideForm.querySelector('[data-field=duration]').value = initial.duration || '';
                slideForm.querySelector('[data-field=order]').value = initial.order || 0;

                imageUrlInput.value = initial.imageUrl || '';

                if (initial.imageUrl) {
                    previewImg.src = initial.imageUrl;
                    previewImg.style.display = 'block';
                    removeBtn.style.display = 'inline-block';
                }

                slideForm.querySelector('[data-action=remove-slide]').addEventListener('click', () => {
                    slideForm.remove();
                    updateSlideOrderNumbers();
                });

                removeBtn.addEventListener('click', () => {
                    imageUrlInput.value = '';
                    imageFileInput.value = '';
                    previewImg.src = '';
                    previewImg.style.display = 'none';
                    removeBtn.style.display = 'none';
                });

                imageFileInput.addEventListener('change', (event) => {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewImg.src = e.target.result;
                            previewImg.style.display = 'block';
                            removeBtn.style.display = 'inline-block';
                            // Clear the URL input if a file is selected
                            imageUrlInput.value = '';
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.getElementById('slidesWrap').appendChild(clone);
                updateSlideOrderNumbers();
            }

            function initSortable() {
                if (sortableInstance) {
                    sortableInstance.destroy();
                }
                const slidesWrap = document.getElementById('slidesWrap');
                sortableInstance = new Sortable(slidesWrap, {
                    handle: '.handle',
                    animation: 150,
                    ghostClass: 'bg-light',
                    onEnd: function() {
                        updateSlideOrderNumbers();
                    }
                });
            }

            async function load(page = 1) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-muted p-4">Carregando…</td></tr>`;

                const params = new URLSearchParams();
                params.set('page', page);

                const q = document.getElementById('q').value.trim();
                if (q) params.set('q', q);

                const res = await apiFetch('/api/v1/carousels?' + params.toString());
                if (!res.ok) {
                    toast('Falha ao carregar carrosséis (' + res.status + ')', 'danger');
                    tbody.innerHTML = `<tr><td colspan="5" class="text-danger p-4">Erro ao carregar.</td></tr>`;
                    return;
                }

                const data = await res.json();
                const rows = data.data || [];
                rows.forEach(r => cacheById.set(String(r.id), r));

                currentPage = data.current_page || 1;
                lastPage = data.last_page || 1;

                info.textContent = `Página ${currentPage} de ${lastPage}. Total: ${data.total} registros.`;
                render(rows);
            }

            function openNew() {
                editingId = null;
                saveError.classList.add('d-none');
                saveError.textContent = '';
                editTitle.textContent = 'Novo Carrossel';

                fillForm({
                    name: '',
                    slug: '',
                    slides: []
                });
                initSortable();
                modal.show();
            }

            function openEdit(id) {
                editingId = String(id);
                saveError.classList.add('d-none');
                saveError.textContent = '';
                editTitle.textContent = 'Editar Carrossel #' + id;

                const item = cacheById.get(String(id));
                if (!item) {
                    toast('Item não encontrado na lista.', 'danger');
                    return;
                }

                // As the index doesn't load relations, we need to fetch the full data for editing
                apiFetch('/api/v1/carousels/' + item.slug).then(res => res.json()).then(slides => {
                    item.slides = slides.data; // The resource returns { data: [...] }
                    fillForm(item);
                    initSortable();
                    modal.show();
                });
            }

            async function save() {
                saveError.classList.add('d-none');
                saveError.textContent = '';

                const payload = buildPayload();
                const isEdit = !!editingId;
                let url = isEdit ? ('/api/v1/carousels/' + editingId) : '/api/v1/carousels';

                // Use POST for FormData requests, and add _method for PUT
                let options = {
                    method: 'POST',
                    body: payload,
                };
                if (isEdit) {
                    payload.append('_method', 'PUT');
                }

                const res = await apiFetch(url, options);

                if (!res.ok) {
                    const resBody = await res.json().catch(() => null);
                    saveError.textContent = resBody ? JSON.stringify(resBody) : ('Erro ao salvar (' + res.status + ')');
                    saveError.classList.remove('d-none');
                    return;
                }

                toast(isEdit ? 'Carrossel atualizado.' : 'Carrossel criado.');
                modal.hide();
                await load(currentPage);
            }

            async function destroy(id) {
                if (!confirm('Excluir carrossel #' + id + '? Esta ação não pode ser desfeita.')) return;

                const res = await apiFetch('/api/v1/carousels/' + id, {
                    method: 'DELETE'
                });
                if (!res.ok) {
                    toast('Falha ao excluir (' + res.status + ')', 'danger');
                    return;
                }

                toast('Carrossel excluído.');
                await load(currentPage);
            }

            // UI events
            document.getElementById('btnNew').addEventListener('click', openNew);
            document.getElementById('btnReload').addEventListener('click', () => load(currentPage));
            document.getElementById('btnSearch').addEventListener('click', () => load(1));
            document.getElementById('btnClear').addEventListener('click', () => {
                document.getElementById('q').value = '';
                load(1);
            });

            document.getElementById('addSlide').addEventListener('click', () => addSlideBlock());

            document.getElementById('prevBtn').addEventListener('click', () => {
                if (currentPage > 1) load(currentPage - 1);
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                if (currentPage < lastPage) load(currentPage + 1);
            });

            document.getElementById('btnSave').addEventListener('click', save);

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');

                if (action === 'edit') openEdit(id);
                if (action === 'delete') destroy(id);
            });

            // initial load
            load(1);
        </script>
    @endpush
@endsection
