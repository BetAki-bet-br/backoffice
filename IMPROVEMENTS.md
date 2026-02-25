# Relatório de Melhorias - Interação Front-Back

**Data:** 2026-02-25
**Escopo:** Análise dos 16 módulos do Backoffice (Blade + Vanilla JS + Laravel API v1)

---

## Sumário Executivo

Após análise módulo a módulo de todas as 16 telas do backoffice, foram identificados **42 pontos de melhoria** agrupados em 7 categorias. As questões mais críticas são: **vulnerabilidade XSS** na renderização de dados via template literals, **duplicação massiva de código JS** entre módulos, e **inconsistências de padrão** entre controllers e views.

---

## 1. Segurança (Severidade: ALTA)

### 1.1 XSS via Template Literals (Todos os módulos)

**Problema:** Todos os módulos renderizam dados da API diretamente em template literals sem sanitização. Dados como `name`, `title`, `slug`, `email` são injetados via `${variable}` diretamente no HTML.

**Exemplo (Banners):**
```js
tbody.innerHTML = rows.map(b => `
  <tr>
    <td class="fw-semibold">${b.slug}</td>    // ← sem escape
    <td>${b.title || '—'}</td>                 // ← sem escape
  </tr>
`).join('');
```

**Módulos afetados:** Banners, Carousels, Providers, Slots, Categories, Lobbies, Menus, Showcases, TopLists, Awards, TopWinners, Footers, Users, Game Extras (14/16 módulos)

**Recomendação:** Criar uma função global `escapeHtml()` no layout e aplicar em toda interpolação de dados da API:
```js
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
```

### 1.2 innerHTML com dados de erro da API (Layout)

**Problema:** Em `showApiError()` (layout `app.blade.php:94-97`), erros de validação são renderizados com `innerHTML`, permitindo que mensagens de erro do backend sejam interpretadas como HTML.

**Recomendação:** Usar `textContent` ou sanitizar o HTML de erro.

---

## 2. Duplicação de Código JS (Severidade: ALTA)

### 2.1 Funções Duplicadas entre Módulos

| Função | Módulos que redefinem | Já existe no layout? |
|--------|----------------------|---------------------|
| `toast()` | Users, Game Extras | **Sim** (global no layout) |
| `apiFetch()` | Users | **Sim** (global no layout) |
| `badgeStatus()` | Users, Showcases | Não (candidata a global) |
| `badge()` | Banners, Awards, Footers | Não (candidata a global) |
| `fmtDate()` | Banners, Awards, Footers, TopLists, TopWinners | Não (candidata a global) |

**Impacto:** ~300+ linhas de código JS duplicado que aumentam manutenção e risco de divergência de comportamento.

**Recomendação:** Mover para o layout global (`app.blade.php`) ou um arquivo JS compartilhado:
- `escapeHtml(str)` — sanitização de HTML
- `fmtDate(dateStr)` — formatação de datas
- `badge(label, colorMap)` — renderização de badges de status
- `badgeStatus(status)` — badge específico para status (active/suspended/disabled)

### 2.2 Padrão de Paginação Duplicado

Cada módulo reimplementa a lógica de paginação (prev/next buttons, estado de cursors, info text). São ~20-30 linhas repetidas em cada módulo.

**Recomendação:** Criar um helper global `PaginationHelper` ou funções `setupCursorPagination()` / `setupPagePagination()` reutilizáveis.

### 2.3 Padrão de Loading/Error na Tabela Duplicado

Todos os módulos repetem:
```js
tbody.innerHTML = `<tr><td colspan="N" class="text-muted p-4">Carregando…</td></tr>`;
// ... e depois para erro:
tbody.innerHTML = `<tr><td colspan="N" class="text-danger p-4">Erro (${status})</td></tr>`;
```

**Recomendação:** Funções globais `tableLoading(tbody, cols)` e `tableError(tbody, cols, status)`.

---

## 3. Inconsistências entre Módulos (Severidade: MÉDIA)

### 3.1 Paginação: Cursor vs Page-based

| Tipo | Módulos |
|------|---------|
| **Cursor** (`cursorPaginate`) | Banners, Carousels, Providers, Slots, Categories, Menus, Showcases, TopLists, Awards, TopWinners, Users |
| **Page-based** (`paginate`) | Footers, Game Extras |
| **Sem paginação** | Lobbies |

**Problema:** O frontend de Footers e Game Extras implementa navegação por número de página (`currentPage`, `lastPage`), enquanto os demais usam cursors. Isso dificulta a reutilização de componentes de paginação.

**Recomendação:** Padronizar para cursor pagination em todos os endpoints ou manter ambos mas com helpers dedicados para cada tipo.

### 3.2 Uso Inconsistente de Transações no Backend

| Com `DB::transaction()` | Sem `DB::transaction()` |
|-------------------------|------------------------|
| TopLists, Awards, TopWinners, Users, Categories | Banners, Carousels, Providers, Slots, Showcases, Menus, Lobbies, Footers |

**Problema:** Operações de escrita em Banners, Carousels, Providers, etc. não usam transações, o que pode resultar em estados inconsistentes em caso de falha parcial.

**Recomendação:** Adicionar `DB::transaction()` em todas as operações de `store()` e `update()` que envolvem múltiplas escritas ou relações.

### 3.3 Documentação OpenAPI Inconsistente

- **Detalhado:** FooterController (parâmetros, schemas, exemplos de resposta)
- **Mínimo:** BannerController, SlotController, TopWinnersController (apenas path + summary)
- **Sem OpenAPI:** Alguns endpoints de sub-recursos

**Recomendação:** Padronizar as annotations em todos os controllers com pelo menos: path, tags, security, summary, request body schema, e response schema.

### 3.4 Audit Trail (created_by/updated_by) Inconsistente

| Com audit | Sem audit |
|-----------|-----------|
| TopLists, Awards, TopWinners, Banners, Users | Carousels, Providers, Slots, Categories, Menus, Showcases, Lobbies, Footers |

**Recomendação:** Padronizar audit fields em todos os controllers que realizam operações de escrita.

### 3.5 Toast Container Duplicado

- **Layout:** Cria container dinamicamente em `toast()` (top-right, id `toastContainer`)
- **Users:** Define container estático no HTML (bottom-right) + função `toast()` própria
- **Game Extras:** Define container estático no HTML (bottom-right) + função `toast()` própria

**Problema:** Posicionamento divergente dos toasts e lógica duplicada.

**Recomendação:** Remover containers/funções locais de Users e Game Extras; usar a global do layout.

---

## 4. UX/Frontend (Severidade: MÉDIA)

### 4.1 Sem Debounce nos Inputs de Busca (Todos os módulos)

**Problema:** Todos os módulos exigem clique no botão "Buscar" para filtrar. Não há debounce no campo de busca para filtragem automática enquanto o usuário digita.

**Recomendação:** Adicionar debounce de ~400ms no evento `input` dos campos de busca:
```js
let debounceTimer;
document.getElementById('q').addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadItems(), 400);
});
```

### 4.2 Sem Loading States nos Botões de Ação (13/16 módulos)

**Problema:** Ao clicar em "Salvar", "Publicar", "Excluir", etc., não há feedback visual de que a ação está em processamento. Apenas Game Extras desabilita o botão "Sincronizar" durante a operação.

**Recomendação:** Implementar padrão global de loading nos botões:
```js
function withLoading(btn, asyncFn) {
  btn.disabled = true;
  const original = btn.textContent;
  btn.textContent = 'Processando...';
  return asyncFn().finally(() => { btn.disabled = false; btn.textContent = original; });
}
```

### 4.3 Sem Confirmação Visual de Ações Destrutivas

**Problema:** Apenas `confirm()` nativo do browser é usado para deleções. Não há modal de confirmação estilizado.

**Recomendação (baixa prioridade):** Substituir `confirm()` por um modal Bootstrap estilizado para manter consistência visual.

### 4.4 Recarga Completa após Mutations

**Problema:** Após criar, editar ou excluir um item, todos os módulos chamam `loadItems(null)` que recarrega a tabela inteira do zero (voltando para a primeira página).

**Recomendação:** Após edição, recarregar a página atual (não voltar para a primeira). Após deleção, atualizar localmente ou recarregar a página atual.

### 4.5 Sem Tratamento de Enter nos Campos de Busca

**Problema:** Pressionar Enter no campo de busca não dispara a busca (exceto quando o browser submete o form). O usuário precisa clicar no botão "Buscar".

**Recomendação:** Adicionar handler de `keydown` para Enter nos campos de busca.

---

## 5. Robustez da Comunicação Front-Back (Severidade: MÉDIA)

### 5.1 Sem Retry em Falhas de Rede

**Problema:** Se uma chamada `fetch()` falhar por erro de rede (timeout, conexão perdida), nenhum módulo tenta novamente. O usuário vê apenas uma mensagem de erro genérica.

**Recomendação:** Implementar retry com backoff exponencial no `apiFetch()` global para erros de rede (não para erros HTTP 4xx/5xx).

### 5.2 Sem Tratamento de Token Expirado durante Operações

**Problema:** O layout detecta 401 e redireciona para login, mas se o token expirar durante o preenchimento de um formulário longo, o usuário perde seus dados ao ser redirecionado.

**Recomendação:** Antes de redirecionar no 401, tentar refresh do token ou alertar o usuário salvando o estado do formulário em `localStorage`.

### 5.3 Catch Ausente em Promises

**Problema:** Várias chamadas `async/await` não possuem `try/catch`, fazendo com que erros de rede (TypeError: Failed to fetch) não sejam tratados e caiam como "unhandled promise rejection".

**Módulos sem try/catch nas chamadas principais:** Users, Game Extras, Menus

**Recomendação:** Envolver todas as chamadas `apiFetch()` em `try/catch` ou adicionar error handling global no `apiFetch()`.

### 5.4 Hardcoded `portal_id=5` em Game Extras

**Problema:** O parâmetro `portal_id` está hardcoded como `5` no frontend de Game Extras:
```js
params.set('portal_id', 5);
```

**Recomendação:** Mover para uma variável de configuração (setting do backend ou variável injetada pelo Blade).

---

## 6. Backend / API Design (Severidade: BAIXA-MÉDIA)

### 6.1 Validação de Status via Strings Mágicas

**Problema:** Vários controllers validam status com arrays inline:
```php
abort_unless(in_array($batch->status, ['draft','review']), 400, '...');
```

**Recomendação:** Usar PHP Enums (disponível em PHP 8.1+) para status em vez de strings mágicas:
```php
enum BatchStatus: string {
  case Draft = 'draft';
  case Review = 'review';
  case Published = 'published';
  case Archived = 'archived';
}
```

### 6.2 Busca com `ilike` sem Índice Explícito

**Problema:** Vários controllers usam `->where('field', 'ilike', '%' . $request->q . '%')` que é específico do PostgreSQL e pode ter performance ruim em tabelas grandes sem índice trigram.

**Recomendação:** Criar índices GIN com `pg_trgm` nas colunas mais buscadas (name, title, slug, email) ou migrar para full-text search do PostgreSQL.

### 6.3 Inconsistência na Resposta de Deleção

| Resposta | Controllers |
|----------|-------------|
| `204 No Content` | TopWinners, TopLists, Showcases, Categories |
| `200 com JSON` | Banners, Footers |
| `200 com mensagem` | Users (422 para self-delete) |

**Recomendação:** Padronizar para `204 No Content` em todas as deleções bem-sucedidas.

### 6.4 Policies/Authorization Parcial

Apenas 2 policies existem:
- `BannerPolicy.php`
- `FooterPolicy.php`

**Problema:** Os demais módulos não possuem policies de autorização, dependendo apenas da autenticação Sanctum. Qualquer usuário autenticado pode realizar qualquer operação em qualquer módulo (exceto Banners e Footers).

**Recomendação:** Implementar policies ou middleware de permissão (Spatie) em todos os controllers para granularidade de acesso.

---

## 7. Organização / Manutenibilidade (Severidade: BAIXA)

### 7.1 JS Inline em Blade Templates

**Problema:** Todo o JavaScript da aplicação (~3000+ linhas) está inline dentro de `@push('scripts')` nos Blade templates. Isso impede:
- Minificação/bundling pelo Vite
- Syntax highlighting e linting no editor
- Caching de JS pelo browser
- Reutilização entre módulos

**Recomendação (longo prazo):** Migrar gradualmente para arquivos `.js` separados importados via Vite, mantendo o pattern de Vanilla JS mas com melhor organização:
```
resources/js/
├── modules/
│   ├── banners.js
│   ├── carousels.js
│   └── ...
└── shared/
    ├── api.js         (apiFetch, error handling)
    ├── ui.js          (toast, badge, table helpers)
    └── pagination.js  (cursor/page pagination)
```

### 7.2 Sem Constantes/Config Centralizadas no Frontend

**Problema:** URLs de API, opções de status, labels de badges etc. estão espalhados por cada módulo.

**Recomendação:** Centralizar em um objeto `window.APP_CONFIG` injetado pelo Blade:
```js
window.APP_CONFIG = {
  apiBase: '/api/v1',
  statuses: { active: 'success', suspended: 'warning', disabled: 'secondary' },
};
```

---

## Resumo por Prioridade

### Prioridade ALTA (Implementar primeiro)
1. **[1.1]** Sanitização XSS — `escapeHtml()` global aplicado em todas as interpolações
2. **[1.2]** `showApiError()` — usar `textContent` ao invés de `innerHTML` para erros
3. **[2.1]** Remover funções duplicadas de `toast()` e `apiFetch()` em Users e Game Extras
4. **[5.3]** Adicionar `try/catch` em chamadas `apiFetch()` sem error handling

### Prioridade MÉDIA (Segundo ciclo)
5. **[2.1]** Extrair `fmtDate()`, `badge()`, `badgeStatus()` para o layout global
6. **[3.2]** Adicionar `DB::transaction()` nos controllers que faltam
7. **[4.2]** Loading states nos botões de ação
8. **[4.4]** Manter página atual após mutations
9. **[4.5]** Handler de Enter nos campos de busca
10. **[5.1]** Retry com backoff no `apiFetch()` para erros de rede
11. **[5.4]** Remover hardcode de `portal_id=5`

### Prioridade BAIXA (Terceiro ciclo)
12. **[2.2]** Helper de paginação reutilizável
13. **[2.3]** Helpers de loading/error para tabelas
14. **[3.1]** Padronizar tipo de paginação
15. **[3.3]** Padronizar annotations OpenAPI
16. **[3.4]** Padronizar audit trail em todos os controllers
17. **[4.1]** Debounce nos inputs de busca
18. **[6.1]** PHP Enums para status
19. **[6.2]** Índices GIN para busca com `ilike`
20. **[6.3]** Padronizar resposta de deleção (204)
21. **[6.4]** Policies/Authorization em todos os módulos
22. **[7.1]** Migrar JS inline para arquivos separados

---

## Matriz de Impacto por Módulo

| Módulo | XSS | JS Dup | Transação | Audit | Pagination | Policy |
|--------|:---:|:------:|:---------:|:-----:|:----------:|:------:|
| Auth/Login | - | - | - | - | - | - |
| Dashboard | - | - | - | - | - | - |
| Banners | X | fmtDate, badge | Falta | OK | Cursor | OK |
| Carousels | X | - | Falta | Falta | Cursor | Falta |
| Providers | X | - | Falta | Falta | Cursor | Falta |
| Slots | X | - | Falta | Falta | Cursor | Falta |
| Categories | X | - | OK | Falta | Cursor | Falta |
| Lobbies | X | - | Falta | Falta | Nenhuma | Falta |
| Menus | X | - | Falta | Falta | Cursor | Falta |
| Showcases | X | badgeStatus | Falta | Falta | Cursor | Falta |
| TopLists | X | fmtDate | OK | OK | Cursor | Falta |
| Awards | X | fmtDate, badge | OK | OK | Cursor | Falta |
| TopWinners | X | fmtDate | OK | OK | Cursor | Falta |
| Footers | X | fmtDate | Falta | Falta | Page | OK |
| Users | X | toast, apiFetch, badgeStatus | OK | OK | Cursor | Falta |
| Game Extras | X | toast | - | - | Page | Falta |

**Legenda:** X = afetado | OK = implementado | Falta = não implementado | - = não aplicável

---

## Conclusão

O backoffice possui uma arquitetura funcional e bem estruturada, mas a abordagem de JS inline em Blade templates levou a uma **duplicação significativa** que dificulta manutenção. A questão mais urgente é a **vulnerabilidade XSS** presente em todos os módulos que renderizam dados. As melhorias recomendadas podem ser implementadas incrementalmente, começando pela sanitização de dados e eliminação de duplicatas, sem necessidade de reestruturação arquitetural.
