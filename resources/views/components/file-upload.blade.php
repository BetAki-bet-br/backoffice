@props([
    'name' => 'cover_url',
    'label' => 'Imagem',
    'required' => false,
    'currentImage' => null,
    'helpText' => 'JPG, PNG, WebP ou GIF. Máximo 2MB.',
])

<div class="file-upload-wrapper mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <!-- Hidden field para armazenar URL atual (compatível com formulários JavaScript) -->
    <input type="hidden" id="f_{{ $name }}" value="{{ $currentImage ?? '' }}">

    <div class="file-upload-container">
        <!-- Current Image Display -->
        @if($currentImage)
            <div class="current-image mb-2">
                <img id="current-{{ $name }}-image" 
                     src="{{ $currentImage }}" 
                     alt="Current {{ $label }}"
                     class="img-thumbnail"
                     style="max-width: 200px; max-height: 200px;">
                <button type="button" 
                        class="btn btn-sm btn-danger ms-2"
                        onclick="removeCurrent{{ ucfirst($name) }}()">
                    Remover Atual
                </button>
            </div>
        @endif

        <!-- File Input -->
        <input type="file"
               id="{{ $name }}"
               name="{{ $name }}"
               class="form-control"
               accept="image/*"
               @if($required) required @endif
               onchange="handleFileSelect{{ ucfirst($name) }}(this)"
               aria-describedby="{{ $name }}_help">

        <!-- Help Text -->
        <small id="{{ $name }}_help" class="form-text text-muted d-block mt-2">
            {{ $helpText }}
        </small>

        <!-- Error Message -->
        <div id="{{ $name }}_error" class="text-danger mt-2 d-none"></div>

        <!-- File Size Info -->
        <div id="{{ $name }}_info" class="text-muted mt-2 d-none"></div>

        <!-- Preview Image -->
        <div id="{{ $name }}_preview_container" class="mt-3 d-none">
            <p class="text-muted small">Preview:</p>
            <img id="{{ $name }}_preview" 
                 class="img-thumbnail"
                 style="max-width: 200px; max-height: 200px; object-fit: cover;">
        </div>

        <!-- Progress Bar -->
        <div id="{{ $name }}_progress" class="progress mt-3 d-none">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 aria-valuenow="0" 
                 aria-valuemin="0" 
                 aria-valuemax="100"
                 style="width: 0%">
            </div>
        </div>
    </div>
</div>

<script>
    const MAX_FILE_SIZE_{{ strtoupper($name) }} = 2 * 1024 * 1024; // 2MB
    const ALLOWED_TYPES_{{ strtoupper($name) }} = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    function resetFileUpload{{ ucfirst($name) }}() {
        const input = document.getElementById('{{ $name }}');
        const hiddenInput = document.getElementById('f_{{ $name }}');
        const errorDiv = document.getElementById('{{ $name }}_error');
        const infoDiv = document.getElementById('{{ $name }}_info');
        const previewContainer = document.getElementById('{{ $name }}_preview_container');
        const previewImg = document.getElementById('{{ $name }}_preview');
        const progressDiv = document.getElementById('{{ $name }}_progress');
        const currentImageContainer = document.getElementById('current-{{ $name }}-image')?.parentElement;

        if (input) input.value = '';
        if (hiddenInput) hiddenInput.value = '';

        if (currentImageContainer) {
            currentImageContainer.style.display = 'none';
        }

        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.add('d-none');
        }

        if (infoDiv) {
            infoDiv.textContent = '';
            infoDiv.classList.add('d-none');
        }

        if (previewImg) previewImg.src = '';
        if (previewContainer) previewContainer.classList.add('d-none');

        if (progressDiv) progressDiv.classList.add('d-none');
    }

    function handleFileSelect{{ ucfirst($name) }}(input) {
        const errorDiv = document.getElementById('{{ $name }}_error');
        const infoDiv = document.getElementById('{{ $name }}_info');
        const previewContainer = document.getElementById('{{ $name }}_preview_container');
        const previewImg = document.getElementById('{{ $name }}_preview');
        const progressDiv = document.getElementById('{{ $name }}_progress');

        // Reset messages
        errorDiv.textContent = '';
        errorDiv.classList.add('d-none');
        infoDiv.textContent = '';
        infoDiv.classList.add('d-none');
        previewContainer.classList.add('d-none');
        progressDiv.classList.add('d-none');

        const file = input.files[0];

        if (!file) {
            return;
        }

        // Validate file type
        if (!ALLOWED_TYPES_{{ strtoupper($name) }}.includes(file.type)) {
            showError{{ ucfirst($name) }}('Tipo de arquivo inválido. Use JPG, PNG, WebP ou GIF.');
            input.value = '';
            return;
        }

        // Validate file size
        if (file.size > MAX_FILE_SIZE_{{ strtoupper($name) }}) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            showError{{ ucfirst($name) }}(`Arquivo muito grande (${sizeMB}MB). Máximo 2MB.`);
            input.value = '';
            return;
        }

        // Show file info
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        infoDiv.textContent = `Arquivo: ${file.name} (${sizeMB}MB)`;
        infoDiv.classList.remove('d-none');

        // Show preview
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    function showError{{ ucfirst($name) }}(message) {
        const errorDiv = document.getElementById('{{ $name }}_error');
        errorDiv.textContent = message;
        errorDiv.classList.remove('d-none');
    }

    function removeCurrent{{ ucfirst($name) }}() {
        const currentImage = document.getElementById('current-{{ $name }}-image');
        if (currentImage) {
            currentImage.parentElement.style.display = 'none';
        }
    }

    // Listener to reset when a modal is closed, assuming Bootstrap Modals
    const fileUploadInputForModal_{{ $name }} = document.getElementById('{{ $name }}');
    if (fileUploadInputForModal_{{ $name }}) {
        const modal = fileUploadInputForModal_{{ $name }}.closest('.modal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                resetFileUpload{{ ucfirst($name) }}();
            });
        }
    }

    // Improved reset on form reset
    document.addEventListener('reset', function(e) {
        const fileUploadInput = document.getElementById('{{ $name }}');
        if (e.target.tagName === 'FORM' && e.target.contains(fileUploadInput)) {
            resetFileUpload{{ ucfirst($name) }}();
        }
    });
</script>
<style>
    .file-upload-wrapper {
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
    }

    .file-upload-container {
        position: relative;
    }

    .current-image {
        border: 2px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        background-color: #fff;
        display: inline-block;
    }

    .form-control[type="file"] {
        padding: 10px;
        border: 2px dashed #dee2e6;
        border-radius: 4px;
        transition: border-color 0.3s ease;
    }

    .form-control[type="file"]:hover {
        border-color: #0d6efd;
    }

    .form-control[type="file"]:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .img-thumbnail {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 4px;
    }
</style>
