# 🎨 Guia de Uso: Componente FileUpload

**Arquivo**: `resources/views/components/file-upload.blade.php`

Este documento mostra como usar o componente FileUpload em suas views Blade.

---

## 📚 Referência do Componente

### Props Disponíveis

```php
<x-file-upload 
    name="cover_url"              // (required) Nome do input
    label="Imagem da Categoria"   // (optional) Label do input
    required="false"               // (optional) Campo obrigatório
    currentImage="$category->cover_url"  // (optional) URL da imagem atual
    helpText="JPG, PNG, WebP ou GIF. Máximo 2MB."  // (optional) Texto de ajuda
/>
```

---

## 💡 Exemplos de Uso

### Exemplo 1: Criar Slot (sem imagem atual)

**View**: `resources/views/slots/create.blade.php`

```blade
<form action="/api/v1/slots" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group mb-3">
        <label for="title" class="form-label">Título *</label>
        <input type="text" id="title" name="title" class="form-control" required>
    </div>

    <div class="form-group mb-3">
        <label for="provider" class="form-label">Provedor *</label>
        <input type="text" id="provider" name="provider" class="form-control" required>
    </div>

    <div class="form-group mb-3">
        <label for="provider_game_id" class="form-label">ID do Jogo *</label>
        <input type="text" id="provider_game_id" name="provider_game_id" class="form-control" required>
    </div>

    <!-- Componente FileUpload -->
    <x-file-upload 
        name="cover_url" 
        label="Imagem da Capa"
    />

    <div class="form-group mt-4">
        <button type="submit" class="btn btn-primary">Criar Slot</button>
        <a href="/slots" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
```

### Exemplo 2: Editar Categoria (com imagem atual)

**View**: `resources/views/categories/edit.blade.php`

```blade
<form action="/api/v1/categories/{{ $category->id }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="form-group mb-3">
        <label for="name" class="form-label">Nome da Categoria *</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            class="form-control @error('name') is-invalid @enderror" 
            value="{{ old('name', $category->name) }}" 
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group mb-3">
        <label for="slug" class="form-label">Slug</label>
        <input 
            type="text" 
            id="slug" 
            name="slug" 
            class="form-control" 
            value="{{ old('slug', $category->slug) }}"
        >
    </div>

    <!-- Componente FileUpload com imagem atual -->
    <x-file-upload 
        name="cover_url" 
        label="Imagem da Categoria"
        :currentImage="$category->cover_url"
        helpText="Deixe em branco para manter a imagem atual. JPG, PNG, WebP ou GIF."
    />

    <div class="form-group mb-3">
        <label for="status" class="form-label">Status *</label>
        <select id="status" name="status" class="form-control" required>
            <option value="active" @selected(old('status', $category->status) === 'active')>Ativo</option>
            <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inativo</option>
        </select>
    </div>

    <div class="form-group mt-4">
        <button type="submit" class="btn btn-primary">Salvar Categoria</button>
        <a href="/categories" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
```

### Exemplo 3: Criar Banner com Traduções

**View**: `resources/views/banners/create.blade.php`

```blade
<form action="/api/v1/banners" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group mb-3">
        <label for="slug" class="form-label">Slug do Banner *</label>
        <input 
            type="text" 
            id="slug" 
            name="slug" 
            class="form-control @error('slug') is-invalid @enderror" 
            required
        >
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Componente FileUpload -->
    <x-file-upload 
        name="cover_url" 
        label="Imagem do Banner"
        helpText="Tamanho recomendado: 1200x300px. Máximo 2MB."
    />

    <div class="form-group mb-3">
        <label for="status" class="form-label">Status *</label>
        <select id="status" name="status" class="form-control" required>
            <option value="draft">Rascunho</option>
            <option value="review">Em Revisão</option>
            <option value="scheduled">Agendado</option>
            <option value="published">Publicado</option>
            <option value="archived">Arquivado</option>
        </select>
    </div>

    <!-- Traduções -->
    <div class="card mt-4 mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Traduções</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Português (BR)</label>
                <input 
                    type="hidden" 
                    name="translations[0][locale]" 
                    value="pt-BR"
                >
                <input 
                    type="text" 
                    name="translations[0][title]" 
                    class="form-control mb-2" 
                    placeholder="Título"
                >
                <textarea 
                    name="translations[0][alt_text]" 
                    class="form-control" 
                    placeholder="Texto alternativo"
                ></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">English</label>
                <input 
                    type="hidden" 
                    name="translations[1][locale]" 
                    value="en"
                >
                <input 
                    type="text" 
                    name="translations[1][title]" 
                    class="form-control mb-2" 
                    placeholder="Title"
                >
                <textarea 
                    name="translations[1][alt_text]" 
                    class="form-control" 
                    placeholder="Alt text"
                ></textarea>
            </div>
        </div>
    </div>

    <div class="form-group mt-4">
        <button type="submit" class="btn btn-primary">Criar Banner</button>
        <a href="/banners" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
```

---

## 🎯 Validação de Formulário

O componente trabalha com a validação padrão do Laravel:

```blade
@error('cover_url')
    <div class="alert alert-danger mt-2">
        {{ $message }}
    </div>
@enderror
```

---

## 🖼️ Características do Componente

### ✅ Funcionalidades Incluídas

1. **Input de Arquivo**
   - Aceita apenas imagens
   - Máximo 2MB
   - Nomes aleatórios no S3

2. **Preview**
   - Mostra preview em tempo real
   - Mostra imagem atual (se houver)
   - Permite remover imagem atual

3. **Validação Client-side**
   - Valida tipo de arquivo
   - Valida tamanho de arquivo
   - Mostra mensagens de erro amigáveis

4. **Acessibilidade**
   - Labels semânticos
   - Aria descriptions
   - Keyboard friendly

5. **Responsividade**
   - Mobile friendly
   - Bootstrap 5 integrado

---

## 🎨 Customização

### Alterar Tamanho Máximo (no componente)

```blade
<x-file-upload 
    name="cover_url"
    label="Grande Imagem"
/>
```

Editar em `resources/views/components/file-upload.blade.php`:
```javascript
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB ao invés de 2MB
```

### Alterar Tipos Aceitos

Em `resources/views/components/file-upload.blade.php`:
```javascript
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
```

E na Blade:
```blade
<input type="file" accept="image/*,.svg" ... />
```

---

## 📱 Exemplo: Layout Bootstrap Completo

```blade
@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Editar Categoria</h3>
                </div>
                <div class="card-body">
                    <form action="/api/v1/categories/{{ $category->id }}" 
                          method="POST" 
                          enctype="multipart/form-data"
                          id="editForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nome *</label>
                            <input 
                                type="text" 
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $category->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-file-upload 
                            name="cover_url"
                            label="Imagem da Categoria"
                            :currentImage="$category->cover_url"
                        />

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Salvar
                            </button>
                            <a href="/categories/{{ $category->id }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('editForm').addEventListener('submit', function(e) {
        // Validação adicional se necessária
        console.log('Formulário enviado');
    });
</script>
@endsection
```

---

## 🔗 Exemplo com Alpine.js (Opcional)

Se você usa Alpine.js para maior interatividade:

```blade
<div x-data="{ uploading: false, progress: 0 }">
    <x-file-upload name="cover_url" label="Imagem" />
    
    <div x-show="uploading" class="mt-3">
        <div class="progress">
            <div class="progress-bar" :style="{ width: progress + '%' }"></div>
        </div>
    </div>
</div>
```

---

## 📝 Notas Importantes

1. **Content-Type**: O formulário deve ter `enctype="multipart/form-data"`
2. **CSRF**: Sempre incluir `@csrf` no form
3. **Método**: Para edit, usar `@method('PUT')` ou `@method('PATCH')`
4. **Validação**: A validação acontece tanto no cliente quanto no servidor
5. **Preview**: O preview é temporário (URL local) - será substituído pela URL do S3 ao salvar

---

## 🆘 Troubleshooting

### Imagem não aparece no preview
- Verificar se o arquivo é uma imagem válida
- Verificar tamanho do arquivo (< 2MB)
- Verificar permissões do navegador

### Validação de arquivo falhando
- Verificar tipo de arquivo (deve ser JPEG, PNG, WebP ou GIF)
- Verificar tamanho do arquivo
- Verificar se está usando `enctype="multipart/form-data"`

### Imagem não é enviada
- Verificar se há espaço em disco
- Verificar credenciais de S3 em `.env`
- Ver logs em `storage/logs/laravel.log`

---

## 📚 Referências

- [Laravel Form Blade Components](https://laravel.com/docs/11.x/blade#components)
- [HTML File Input](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file)
- [AWS S3 Laravel](https://laravel.com/docs/11.x/filesystem#s3-driver)
- [Bootstrap 5 Forms](https://getbootstrap.com/docs/5.0/forms/overview/)

---

**Versão**: 1.0.0  
**Última atualização**: 19 de janeiro de 2026
