# Plano de Implementação - Testes E2E com Cypress

## 1. Visão Geral da Aplicação

**Stack:** Laravel 12 + Blade Templates + Vanilla JS + Bootstrap 5 + Vite
**Auth:** Laravel Sanctum (Bearer Token via localStorage/sessionStorage)
**API:** REST (`/api/v1/...`) com JSON

### Módulos Mapeados (16 telas)

| # | Módulo | Rota | Operações | Prioridade |
|---|--------|------|-----------|------------|
| 1 | Login | `/login` | Auth (email/password) | Alta |
| 2 | Dashboard | `/` | Visualização KPIs | Média |
| 3 | Banners | `/banners` | CRUD + Filtros + Publish | Alta |
| 4 | Carrosséis | `/carousels` | CRUD + Slides | Alta |
| 5 | Providers | `/providers` | List + Edit + Sync + View Games | Média |
| 6 | Slots | `/slots` | CRUD + Filtros | Alta |
| 7 | Categorias | `/categories` | CRUD + Slots Mgmt + Sync | Alta |
| 8 | Lobbies | `/lobbies` | Config por vertical | Média |
| 9 | Menus | `/menus` | CRUD + Reorder | Média |
| 10 | Showcases | `/showcases` | CRUD + Slots Mgmt | Média |
| 11 | Top Lists | `/top-lists` | CRUD + Slots + Publish | Média |
| 12 | Awards | `/awards` | CRUD + Results + Publish/Archive | Média |
| 13 | Top Winners | `/top-winners` | CRUD + Winners + Publish/Archive | Média |
| 14 | Footers | `/footers` | CRUD + Links + Publish | Média |
| 15 | Users | `/users` | CRUD + Roles | Alta |
| 16 | Game Extras | `/game-extras` | Read-only + Sync | Baixa |

---

## 2. Configuração do Cypress

### 2.1 Instalação de dependências

```bash
npm install --save-dev cypress
```

### 2.2 Estrutura de diretórios

```
cypress/
├── cypress.config.js          # Configuração principal
├── e2e/                       # Specs de teste
│   ├── auth/
│   │   └── login.cy.js
│   ├── dashboard/
│   │   └── dashboard.cy.js
│   ├── banners/
│   │   └── banners.cy.js
│   ├── carousels/
│   │   └── carousels.cy.js
│   ├── providers/
│   │   └── providers.cy.js
│   ├── slots/
│   │   └── slots.cy.js
│   ├── categories/
│   │   └── categories.cy.js
│   ├── lobbies/
│   │   └── lobbies.cy.js
│   ├── menus/
│   │   └── menus.cy.js
│   ├── showcases/
│   │   └── showcases.cy.js
│   ├── toplists/
│   │   └── toplists.cy.js
│   ├── awards/
│   │   └── awards.cy.js
│   ├── topwinners/
│   │   └── topwinners.cy.js
│   ├── footers/
│   │   └── footers.cy.js
│   ├── users/
│   │   └── users.cy.js
│   └── game-extras/
│       └── game-extras.cy.js
├── support/
│   ├── commands.js            # Custom commands (login, intercepts)
│   ├── e2e.js                 # Setup global
│   └── page-objects/          # Page Objects
│       ├── BasePage.js
│       ├── LoginPage.js
│       ├── DashboardPage.js
│       ├── BannersPage.js
│       ├── CarouselsPage.js
│       ├── ProvidersPage.js
│       ├── SlotsPage.js
│       ├── CategoriesPage.js
│       ├── LobbiesPage.js
│       ├── MenusPage.js
│       ├── ShowcasesPage.js
│       ├── TopListsPage.js
│       ├── AwardsPage.js
│       ├── TopWinnersPage.js
│       ├── FootersPage.js
│       ├── UsersPage.js
│       └── GameExtrasPage.js
└── fixtures/                  # Dados mock para intercepts
    ├── auth/
    │   ├── login-success.json
    │   └── me.json
    ├── banners/
    │   ├── list.json
    │   └── single.json
    ├── carousels/
    │   ├── list.json
    │   └── single.json
    ├── slots/
    │   ├── list.json
    │   └── single.json
    ├── categories/
    │   ├── list.json
    │   └── single.json
    ├── providers/
    │   ├── list.json
    │   └── single.json
    ├── menus/
    │   ├── list.json
    │   └── single.json
    ├── showcases/
    │   ├── list.json
    │   └── single.json
    ├── toplists/
    │   ├── list.json
    │   └── single.json
    ├── awards/
    │   ├── list.json
    │   └── single.json
    ├── topwinners/
    │   ├── list.json
    │   └── single.json
    ├── footers/
    │   ├── list.json
    │   └── single.json
    ├── users/
    │   ├── list.json
    │   └── single.json
    ├── lobbies/
    │   └── config.json
    └── game-extras/
        └── overview.json
```

### 2.3 Configuração (`cypress.config.js`)

- `baseUrl`: `http://localhost:8000`
- `viewportWidth`: 1280, `viewportHeight`: 720
- `defaultCommandTimeout`: 10000
- `video`: false (CI pode ativar)
- `specPattern`: `cypress/e2e/**/*.cy.js`

### 2.4 Scripts no `package.json`

```json
{
  "cypress:open": "cypress open",
  "cypress:run": "cypress run",
  "cypress:run:headed": "cypress run --headed"
}
```

---

## 3. Page Objects Pattern

### 3.1 `BasePage` (classe base)

Encapsula comportamentos comuns a todas as páginas:

- **Navegação:** `visit()`, `verifyUrl()`
- **Sidebar:** `clickSidebarLink(name)`, `verifySidebarActive(name)`
- **Topbar:** `verifyTopbar()`, `clickLogout()`
- **Tabela:** `getTableRows()`, `getTableRowByIndex(i)`, `verifyTableHasRows()`
- **Modais:** `openCreateModal()`, `closeModal()`, `submitModal()`, `getModal()`
- **Filtros:** `searchBy(text)`, `filterByStatus(status)`, `clearFilters()`
- **Paginação:** `clickNextPage()`, `clickPrevPage()`, `verifyPaginationInfo()`
- **Toast:** `verifyToast(message, type)`
- **Ações de linha:** `clickRowAction(rowIndex, action)`

### 3.2 Page Objects por módulo

Cada Page Object herda de `BasePage` e adiciona:

- **Seletores específicos** da página (campos de formulário, botões, modais)
- **Métodos de domínio** (ex: `fillBannerForm(data)`, `publishBanner(index)`)
- **Assertions de domínio** (ex: `verifyBannerInTable(slug)`)

---

## 4. Estratégia de Testes - Intercept de API

Como a aplicação usa Blade + Vanilla JS com chamadas `fetch()` para a API, os testes usarão **`cy.intercept()`** para mockar as respostas da API. Isso permite:

- Testes rápidos e determinísticos (sem depender de banco/backend real)
- Controle total sobre os dados exibidos
- Simulação de erros (401, 422, 500)
- Teste de estados vazios, loading, etc.

### Custom Command: `cy.login()`

Injeta o token no `localStorage` para simular autenticação sem passar pela tela de login em cada teste:

```js
Cypress.Commands.add('login', () => {
  localStorage.setItem('betaki_admin_token', 'fake-test-token');
  localStorage.setItem('betaki_admin_expires_at', '2099-12-31');
});
```

---

## 5. Use Cases por Módulo e Cobertura

### 5.1 Auth/Login (6 use cases)
- [x] UC1: Login com credenciais válidas → redirect para dashboard
- [x] UC2: Login com credenciais inválidas → mensagem de erro
- [x] UC3: Login com campos vazios → validação HTML5
- [x] UC4: Checkbox "Manter conectado" → token em localStorage vs sessionStorage
- [x] UC5: Redirect para /login quando sem token
- [x] UC6: Logout → limpa token e redirect

### 5.2 Dashboard (3 use cases)
- [x] UC7: Exibe cards de KPI (Banners, Slots, Menus, Footers)
- [x] UC8: Sidebar com links de navegação
- [x] UC9: Topbar com nome do usuário e botão sair

### 5.3 Banners (8 use cases)
- [x] UC10: Lista banners com tabela paginada
- [x] UC11: Filtra por status (draft/published/archived)
- [x] UC12: Filtra por vertical (casino/live_casino/sportbook)
- [x] UC13: Busca por slug
- [x] UC14: Criar banner via modal (preencher form + submit)
- [x] UC15: Editar banner existente
- [x] UC16: Deletar banner
- [x] UC17: Publicar banner

### 5.4 Carrosséis (6 use cases)
- [x] UC18: Lista carrosséis
- [x] UC19: Busca por nome/slug
- [x] UC20: Criar carrossel com slides
- [x] UC21: Editar carrossel
- [x] UC22: Adicionar/remover slides
- [x] UC23: Deletar carrossel

### 5.5 Providers (5 use cases)
- [x] UC24: Lista providers com filtros
- [x] UC25: Filtra por vertical e status
- [x] UC26: Editar provider (nome, status, verticals)
- [x] UC27: Visualizar games do provider
- [x] UC28: Sync providers

### 5.6 Slots (6 use cases)
- [x] UC29: Lista slots com paginação
- [x] UC30: Busca por título/provider/game_id
- [x] UC31: Filtra por status
- [x] UC32: Criar slot
- [x] UC33: Editar slot
- [x] UC34: Deletar slot

### 5.7 Categorias (7 use cases)
- [x] UC35: Lista categorias
- [x] UC36: Filtra por vertical, tipo, status
- [x] UC37: Criar categoria
- [x] UC38: Editar categoria
- [x] UC39: Gerenciar slots da categoria (add/remove/reorder)
- [x] UC40: Sync categorias
- [x] UC41: Deletar categoria

### 5.8 Lobbies (4 use cases)
- [x] UC42: Carrega config do lobby Slots
- [x] UC43: Carrega config do lobby Live
- [x] UC44: Adicionar/editar seção
- [x] UC45: Salvar alterações

### 5.9 Menus (6 use cases)
- [x] UC46: Lista menus
- [x] UC47: Busca por nome/slug
- [x] UC48: Criar menu
- [x] UC49: Editar menu
- [x] UC50: Reordenar menus (drag-and-drop ou up/down)
- [x] UC51: Deletar menu

### 5.10 Showcases (6 use cases)
- [x] UC52: Lista showcases
- [x] UC53: Filtra por status e tipo
- [x] UC54: Criar showcase (manual/dynamic)
- [x] UC55: Editar showcase
- [x] UC56: Gerenciar slots do showcase
- [x] UC57: Deletar showcase

### 5.11 Top Lists (6 use cases)
- [x] UC58: Lista top lists
- [x] UC59: Filtra por status
- [x] UC60: Criar top list
- [x] UC61: Editar top list
- [x] UC62: Gerenciar slots da top list
- [x] UC63: Publicar top list

### 5.12 Awards (6 use cases)
- [x] UC64: Lista awards
- [x] UC65: Filtra por vertical e status
- [x] UC66: Criar award batch
- [x] UC67: Editar award batch
- [x] UC68: Gerenciar resultados
- [x] UC69: Publicar/Arquivar batch

### 5.13 Top Winners (6 use cases)
- [x] UC70: Lista top winners
- [x] UC71: Filtra por vertical e status
- [x] UC72: Criar winner batch
- [x] UC73: Editar winner batch
- [x] UC74: Gerenciar winners
- [x] UC75: Publicar/Arquivar batch

### 5.14 Footers (7 use cases)
- [x] UC76: Lista footers
- [x] UC77: Filtra por status e country
- [x] UC78: Criar footer com translations
- [x] UC79: Editar footer
- [x] UC80: Gerenciar links do footer
- [x] UC81: Publicar footer
- [x] UC82: Deletar footer

### 5.15 Users (5 use cases)
- [x] UC83: Lista usuários
- [x] UC84: Busca por nome/email
- [x] UC85: Criar usuário com roles
- [x] UC86: Editar usuário
- [x] UC87: Deletar usuário

### 5.16 Game Extras (3 use cases)
- [x] UC88: Lista game extras com paginação
- [x] UC89: Busca por external_id
- [x] UC90: Sync game extras

**Total: 90 use cases mapeados → 100% cobertura planejada, mínimo 70% implementados.**

---

## 6. Ordem de Implementação (Fases)

### Fase 1 - Setup e Infraestrutura
1. Instalar Cypress e dependências
2. Criar `cypress.config.js`
3. Criar estrutura de diretórios
4. Implementar `BasePage.js`
5. Implementar `commands.js` (login, intercepts genéricos)
6. Implementar `e2e.js` (setup global)
7. Adicionar scripts ao `package.json`

### Fase 2 - Auth + Dashboard + Navegação
8. Criar fixtures de auth
9. Implementar `LoginPage.js`
10. Implementar `login.cy.js` (UC1-UC6)
11. Implementar `DashboardPage.js`
12. Implementar `dashboard.cy.js` (UC7-UC9)

### Fase 3 - CRUD Principal (Alta Prioridade)
13. Fixtures + `BannersPage.js` + `banners.cy.js` (UC10-UC17)
14. Fixtures + `SlotsPage.js` + `slots.cy.js` (UC29-UC34)
15. Fixtures + `CarouselsPage.js` + `carousels.cy.js` (UC18-UC23)
16. Fixtures + `CategoriesPage.js` + `categories.cy.js` (UC35-UC41)
17. Fixtures + `UsersPage.js` + `users.cy.js` (UC83-UC87)

### Fase 4 - Módulos Secundários (Média Prioridade)
18. Fixtures + `ProvidersPage.js` + `providers.cy.js` (UC24-UC28)
19. Fixtures + `MenusPage.js` + `menus.cy.js` (UC46-UC51)
20. Fixtures + `ShowcasesPage.js` + `showcases.cy.js` (UC52-UC57)
21. Fixtures + `TopListsPage.js` + `toplists.cy.js` (UC58-UC63)
22. Fixtures + `AwardsPage.js` + `awards.cy.js` (UC64-UC69)
23. Fixtures + `TopWinnersPage.js` + `topwinners.cy.js` (UC70-UC75)
24. Fixtures + `FootersPage.js` + `footers.cy.js` (UC76-UC82)
25. Fixtures + `LobbiesPage.js` + `lobbies.cy.js` (UC42-UC45)

### Fase 5 - Game Extras + Ajustes
26. Fixtures + `GameExtrasPage.js` + `game-extras.cy.js` (UC88-UC90)
27. Revisão geral e ajustes de seletores
28. Documentação final

---

## 7. Convenções e Boas Práticas

- **Seletores:** Priorizar `data-cy` attributes onde possível; usar `#id` e `.class` quando já existem no HTML
- **Intercepts:** Toda chamada à API deve ser interceptada com `cy.intercept()` e usar fixtures
- **Isolamento:** Cada spec é independente; `beforeEach()` faz login + setup de intercepts
- **Assertions:** Verificar estados visuais (texto, classes CSS, visibilidade) e chamadas à API (via `cy.wait('@alias')`)
- **Timeouts:** Usar `defaultCommandTimeout` global; evitar `cy.wait(ms)` fixos
- **Page Objects:** Retornam `this` para encadeamento; não fazem assertions (quem faz assertion é o spec)
