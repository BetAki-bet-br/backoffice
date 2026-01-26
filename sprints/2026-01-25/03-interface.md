# 03 - Interface Frontend (Sistema de Gerenciamento de Bots Telegram)

**Versão**: 1.0  
**Data**: Janeiro 2026  
**Status**: Produção

---

## 📋 Índice

1. [Visão Geral da Interface](#visão-geral-da-interface)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estrutura de Pastas](#estrutura-de-pastas)
4. [Componentes Vue 3](#componentes-vue-3)
5. [Composables e State Management](#composables-e-state-management)
6. [Sistema de Tipos TypeScript](#sistema-de-tipos-typescript)
7. [Roteamento e Navegação](#roteamento-e-navegação)
8. [Padrões de Design](#padrões-de-design)
9. [Integração com API](#integração-com-api)
10. [Guia de Uso](#guia-de-uso)
11. [Troubleshooting](#troubleshooting)

---

## Visão Geral da Interface

A interface frontend foi desenvolvida com **Vue 3** usando a **Composition API**, fornecendo uma experiência moderna e responsiva para gerenciar bots Telegram, seus fluxos e análise de dados em tempo real.

### Principais Funcionalidades

#### 1. **Gerenciamento de Bots**
- Listar todos os bots com status visual
- Criar novo bot com validação de campos
- Editar configurações de bot existente
- Visualizar detalhes completos do bot
- Gerenciar webhook (configurar, testar, resetar)
- Ativar/pausar/desativar bots

#### 2. **Builder de Fluxos**
- Interface drag-and-drop intuitiva
- 6 tipos de passos: Mensagem, Botão, Entrada, Validação, Condição, Ação
- Editor de propriedades em tempo real
- Visualização da cadeia de fluxo
- Salvar como rascunho e publicar
- Duplicar fluxos existentes

#### 3. **Analytics e Insights**
- Dashboard com KPIs principais
- Gráficos de tendência de mensagens
- Análise de taxa de sucesso de validações
- Top 5 bots mais ativos
- Quebra de status de validações (aprovadas/falhadas/pendentes)
- Exportar dados em CSV

#### 4. **Gerenciamento de Usuários**
- Listar usuários validados/falhados/pendentes
- Visualizar detalhes de cada usuário
- Histórico de erros de validação
- Data de última interação

---

## Stack Tecnológico

### Frontend Framework
- **Vue 3** (Composition API)
- **TypeScript** 5.x para type safety
- **Vite** como build tool
- **Vue Router** para navegação

### Styling
- **TailwindCSS v4** para utilitários de CSS
- Design responsivo mobile-first
- Componentes sem dependências externas

### HTTP Client
- **Axios** para requisições API
- Interceptors para autenticação
- Error handling centralizado

### Estado e Lógica
- **Composables** para compartilhamento de lógica
- **Refs e Computed** do Vue 3
- **LocalStorage** para cache local

### Utilitários
- **date-fns** para manipulação de datas (opcional)
- **Chart.js** para gráficos avançados (opcional)

---

## Estrutura de Pastas

```
resources/
├── js/
│   ├── types/
│   │   └── telegram.ts          # 16 interfaces TypeScript
│   │
│   ├── composables/
│   │   ├── useTelegramBots.ts   # Lógica CRUD de bots
│   │   ├── useBotFlows.ts       # Lógica CRUD de fluxos
│   │   └── useBotStatistics.ts  # Lógica de analytics
│   │
│   ├── pages/
│   │   └── TelegramBots/
│   │       ├── ListBots.vue           # Listagem de bots (grid/cards)
│   │       ├── CreateEditBot.vue      # Formulário criar/editar bot
│   │       ├── BotDetail.vue          # Detalhes e webhook config
│   │       ├── FlowList.vue           # Listagem de fluxos
│   │       ├── FlowBuilder.vue        # Editor visual de fluxos
│   │       ├── AnalyticsDashboard.vue # Dashboard de analytics
│   │       └── UsersList.vue          # Listagem de usuários
│   │
│   ├── components/                    # (Componentes reutilizáveis)
│   │   ├── StatsCard.vue
│   │   ├── Modal.vue
│   │   └── DataTable.vue
│   │
│   ├── App.vue                        # Componente raiz
│   ├── main.ts                        # Entry point
│   └── router.ts                      # Configuração de rotas
│
└── views/                             # (Se usando renderização server-side)
```

---

## Componentes Vue 3

### 1. ListBots.vue - Listagem de Bots

**Caminho**: `resources/js/pages/TelegramBots/ListBots.vue`

**Responsabilidade**: Exibir todos os bots em formato de grid/cards

**Features**:
- Grid responsivo (1 col mobile, 2 cols tablet, 3 cols desktop)
- Cards com status visual (verde=ativo, cinza=inativo, amarelo=pausado)
- Ícone de webhook status
- Contador de fluxos por bot
- Botões de ação: Detalhes, Gerenciar Fluxos, Deletar
- Loading state com spinner
- Empty state com CTA
- Filtros por status (via computed properties)

**Exemplo de Uso**:
```vue
<template>
  <ListBots />
</template>

<script setup lang="ts">
import ListBots from '@/pages/TelegramBots/ListBots.vue'
</script>
```

**Props**: Nenhuma (usa composable interno)

**Emits**: Nenhum (usa router.push para navegação)

---

### 2. CreateEditBot.vue - Criar/Editar Bot

**Caminho**: `resources/js/pages/TelegramBots/CreateEditBot.vue`

**Responsabilidade**: Formulário para criar novo bot ou editar existente

**Features**:
- Modo duplo: create (novo) / edit (existente)
- 3 seções: Informações Básicas, Configuração da API, Configuração do Grupo
- Validação de campos (tipo, required, padrão)
- Desabilitar edição de token e username em modo edit
- Campo de token mascarado (type="password")
- Campo de modo debug (toggle checkbox)
- Loading states
- Error handling com mensagens claras
- Redirecionamento após sucesso

**Campos do Formulário**:
```typescript
{
  name: string                    // Nome do bot
  bot_token: string              // Token do BotFather (não editável)
  username: string               // Username Telegram (não editável)
  description: string            // Descrição opcional
  api_url: string                // URL da API de validação
  api_key: string                // Chave da API (mascarada)
  portal_id: number              // ID do portal
  group_chat_id?: string         // ID do grupo (opcional)
  group_invite_link?: string     // Link de convite (opcional)
  register_url?: string          // URL de cadastro (opcional)
  debug_mode: boolean            // Toggle de debug
  status?: 'active' | 'inactive' | 'paused'  // (edit only)
}
```

**Validações Frontend**:
```typescript
// Nome: obrigatório
// Username: obrigatório, 5-32 chars, apenas alphanumericoe underscore
// Bot Token: obrigatório, desabilitado em edit
// API URL: obrigatório, deve ser URL válida
// API Key: obrigatório
// Portal ID: obrigatório, número > 0
```

**Exemplo de Uso**:
```vue
<!-- Criar novo bot -->
<router-link to="/telegram-bots/create">
  + Novo Bot
</router-link>

<!-- Editar bot existente -->
<router-link :to="`/telegram-bots/${botId}/edit`">
  Editar
</router-link>
```

---

### 3. BotDetail.vue - Detalhes e Webhook

**Caminho**: `resources/js/pages/TelegramBots/BotDetail.vue`

**Responsabilidade**: Exibir detalhes completos do bot e gerenciar webhook

**Features**:
- Cards de status: Status, Fluxos Ativos, Usuários, Mensagens
- Painel de webhook com:
  - URL do webhook (copiável)
  - Status de configuração (data/hora)
  - Status de teste (OK/Falhou)
  - Último erro (se houver)
  - Botões: Configurar, Testar, Resetar
- Informações do bot (username, portal ID, API URL, debug mode)
- Link para editar configurações
- Link para gerenciar fluxos
- Loading states
- Success messages com auto-dismiss
- Error handling

**Webhook URL Format**:
```
https://yourdomain.com/api/v1/telegram-bots/webhook/{botId}/updates
```

**Statuses do Webhook**:
- ✓ Configurado: Data/hora da última configuração
- ✗ Não configurado: Aguardando configuração
- ✓ OK: Teste bem-sucedido
- ✗ Falhou: Mostra erro da API Telegram

**Exemplo de Uso**:
```vue
<!-- Navegação automática -->
<router-link :to="`/telegram-bots/${botId}`">
  {{ bot.name }}
</router-link>
```

---

### 4. FlowList.vue - Listagem de Fluxos

**Caminho**: `resources/js/pages/TelegramBots/FlowList.vue`

**Responsabilidade**: Exibir fluxos de um bot específico

**Features**:
- Cards por fluxo com:
  - Nome do fluxo
  - Status badge (Rascunho/Ativo/Arquivado)
  - Versão
  - Contador de passos
  - Badge "Padrão" (se for fluxo padrão)
  - Data de publicação/criação
- Abas de filtro: Todos, Rascunhos, Ativos
- Botões de ação:
  - Editar (em todos)
  - Duplicar (menu ⋯)
  - Publicar (rascunhos)
  - Deletar (ativos)
- Empty state com CTA
- Loading states
- Confirmação de deletion

**Estados de Fluxo**:
```
draft    → Rascunho (pode editar/publicar)
active   → Ativo (pode duplicar/deletar)
archived → Arquivado (apenas leitura)
```

**Exemplo de Uso**:
```vue
<!-- Navegar para fluxos de um bot -->
<router-link :to="`/telegram-bots/${botId}/flows`">
  Gerenciar Fluxos
</router-link>
```

---

### 5. FlowBuilder.vue - Editor Visual de Fluxos

**Caminho**: `resources/js/pages/TelegramBots/FlowBuilder.vue`

**Responsabilidade**: Interface drag-and-drop para criar/editar fluxos

**Features**:
- Sidebar com tipos de passos (draggável)
- Canvas central onde arrastar passos
- Reordenação de passos
- Painel de propriedades (direita)
- Seleção de passo para editar
- Variáveis disponíveis: {{ user.email }}, {{ user.phone }}, {{ bot.name }}
- Modo duplo: create / edit
- Save as draft / Publish
- Error handling

**Tipos de Passos Disponíveis**:

| Tipo | Nome | Descrição | Dados |
|------|------|-----------|-------|
| `message` | Mensagem | Enviar mensagem ao usuário | `content` |
| `button` | Botão | Exibir opções com botões | `content`, `options` |
| `input` | Entrada | Coletar input do usuário | `content`, `field_type` |
| `validation` | Validação | Validar dados com API | `api_endpoint`, `field_map` |
| `condition` | Condição | Desviar fluxo condicionalmente | `condition`, `branches` |
| `action` | Ação | Executar ação (email, webhook) | `action_type`, `config` |

**Estrutura de Dados - BotFlowStep**:
```typescript
{
  id: number                      // UUID único
  flow_id: number                 // ID do fluxo pai
  type: string                    // Tipo do passo
  order: number                   // Ordem na sequência
  data: {
    content?: string              // Conteúdo da mensagem
    options?: Array               // Opções de botões
    field_type?: string          // Tipo de campo (texto, email, tel)
    api_endpoint?: string        // Endpoint de validação
    condition?: string           // Expressão de condição
    action_type?: string         // Tipo de ação (email, webhook)
    config?: Record<string, any> // Configuração específica
  }
  created_at: string
  updated_at: string
}
```

**Variáveis Interpoláveis**:
```
{{ user.email }}      → Email do usuário
{{ user.phone }}      → Telefone do usuário
{{ user.first_name }} → Primeiro nome
{{ user.last_name }}  → Sobrenome
{{ bot.name }}        → Nome do bot
{{ bot.username }}    → Username do bot
```

**Exemplo de Uso - Criar Fluxo**:
```vue
<!-- Navegar para novo fluxo -->
<router-link :to="`/telegram-bots/${botId}/flows/new`">
  + Novo Fluxo
</router-link>
```

**Exemplo de Fluxo Completo**:
```
Passo 1: Mensagem
├─ "Olá {{ user.first_name }}, bem-vindo ao {{ bot.name }}!"
│
Passo 2: Botão
├─ "Qual é seu email?"
├─ Opções: ["Usar email registrado", "Fornecer novo email"]
│
Passo 3: Entrada (condicional)
├─ Se "Novo email" → Coletar email com validação
├─ Se "Email registrado" → Pular para validação
│
Passo 4: Validação
├─ POST /api/validate-email
├─ Field map: { email: "{{ user_input }}" }
│
Passo 5: Condição
├─ Se status === "valid" → Mensagem de sucesso
├─ Se status === "invalid" → Mensagem de erro
│
Passo 6: Ação
├─ Enviar email de confirmação
└─ Webhook para sistema externo
```

---

### 6. AnalyticsDashboard.vue - Analytics

**Caminho**: `resources/js/pages/TelegramBots/AnalyticsDashboard.vue`

**Responsabilidade**: Dashboard com KPIs e insights

**Features**:
- Seletor de período (7, 30, 90 dias)
- 4 cards principais:
  - Total de Bots (com contador de ativos)
  - Mensagens Enviadas
  - Mensagens Recebidas
  - Taxa de Sucesso (%)
- Gráfico de tendência (últimos 7 dias)
- Breakdown de validações (Pizza/Progress Bar)
- Tabela de top 5 bots
- Export CSV
- Loading states

**KPIs Exibidos**:
```
Total de Bots          → Número total + ativos
Mensagens Enviadas     → Agregado do período
Mensagens Recebidas    → Agregado do período
Taxa de Sucesso        → (validadas / total) * 100
Validadas              → Usuários com sucesso
Falhadas               → Usuários com erro
Pendentes              → Ainda processando
```

**Gráficos**:

1. **Tendência de Mensagens** (Bar Chart)
   - Eixo X: Dias (últimos 7)
   - Eixo Y: Quantidade mensagens
   - Barra azul = mensagens do dia

2. **Status de Validações** (Progress Bar)
   - Verde: % validadas
   - Vermelho: % falhadas
   - Amarelo: % pendentes

3. **Top Bots** (Data Table)
   - Bot name
   - Mensagens enviadas
   - Mensagens recebidas
   - Usuários ativos

**Exemplo de Uso**:
```vue
<template>
  <AnalyticsDashboard />
</template>

<script setup lang="ts">
import AnalyticsDashboard from '@/pages/TelegramBots/AnalyticsDashboard.vue'
</script>
```

---

### 7. UsersList.vue - Listagem de Usuários

**Caminho**: `resources/js/pages/TelegramBots/UsersList.vue`

**Responsabilidade**: Listar usuários por status de validação

**Features**:
- Abas: Validados, Falhados, Pendentes
- Tabela de usuários com colunas:
  - Email
  - Telefone
  - Nome do Bot
  - Status (badge)
  - Data de criação
  - Ação (Ver Detalhes)
- Painel de detalhes (drawer/modal)
  - Email, Telefone, Bot, Status
  - Data de criação e última interação
  - Erro de validação (se houver)
- Empty state
- Loading states

**Dados por Usuário**:
```typescript
{
  id: number
  email: string
  phone?: string
  first_name?: string
  last_name?: string
  bot_name: string
  status: 'validated' | 'failed' | 'pending'
  validation_error?: string
  created_at: string
  last_interaction_at?: string
}
```

**Exemplo de Uso**:
```vue
<!-- Link do dashboard -->
<router-link to="/telegram-bots/users">
  Ver Usuários
</router-link>
```

---

## Composables e State Management

### 1. useTelegramBots.ts

**Responsabilidade**: Gerenciar estado e requisições de bots

**Estado (Refs)**:
```typescript
const bots = ref<TelegramBot[]>([])
const currentBot = ref<TelegramBot | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
```

**Métodos**:
```typescript
// CRUD
fetchBots(): Promise<TelegramBot[]>
fetchBot(id: number): Promise<TelegramBot>
createBot(data: CreateBotPayload): Promise<TelegramBot>
updateBot(id: number, data: UpdateBotPayload): Promise<TelegramBot>
deleteBot(id: number): Promise<void>

// Webhook
setupWebhook(botId: number): Promise<void>
testWebhook(botId: number): Promise<void>
resetWebhook(botId: number): Promise<void>
```

**Computed**:
```typescript
hasBots: boolean                  // bots.length > 0
activeBots: TelegramBot[]         // status === 'active'
inactiveBots: TelegramBot[]       // status !== 'active'
```

**Exemplo de Uso**:
```typescript
import { useTelegramBots } from '@/composables/useTelegramBots'

const { bots, fetchBots, createBot, loading, error } = useTelegramBots()

// Carregar bots
onMounted(() => {
  fetchBots()
})

// Criar novo bot
const handleCreate = async (data) => {
  await createBot(data)
  // Bots lista vai atualizar automaticamente
}
```

---

### 2. useBotFlows.ts

**Responsabilidade**: Gerenciar estado e requisições de fluxos

**Estado (Refs)**:
```typescript
const flows = ref<BotFlow[]>([])
const currentFlow = ref<BotFlowDetail | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
```

**Métodos**:
```typescript
// CRUD
fetchFlows(botId: number): Promise<BotFlow[]>
fetchFlow(id: number): Promise<BotFlowDetail>
createFlow(botId: number, data: CreateFlowPayload): Promise<BotFlow>
updateFlow(id: number, data: UpdateFlowPayload): Promise<BotFlow>
deleteFlow(id: number): Promise<void>

// Operações especiais
duplicateFlow(flowId: number): Promise<BotFlow>
publishFlow(flowId: number): Promise<BotFlow>
```

**Computed**:
```typescript
hasFlows: boolean                 // flows.length > 0
activeFlows: BotFlow[]            // status === 'active'
draftFlows: BotFlow[]             // status === 'draft'
defaultFlow: BotFlow | null       // is_default === true
```

**Exemplo de Uso**:
```typescript
import { useBotFlows } from '@/composables/useBotFlows'

const { flows, createFlow, publishFlow } = useBotFlows()

// Criar fluxo
const newFlow = await createFlow(botId, {
  name: 'Welcome Flow',
  steps: [...]
})

// Publicar rascunho
await publishFlow(newFlow.id)
```

---

### 3. useBotStatistics.ts

**Responsabilidade**: Gerenciar dados de analytics

**Estado (Refs)**:
```typescript
const statistics = ref<BotStatistics | null>(null)
const chartData = ref<BotChartData[]>([])
const validatedUsers = ref<BotUser[]>([])
const failedUsers = ref<BotUser[]>([])
const messageLogs = ref<BotMessage[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
```

**Métodos**:
```typescript
// Analytics
fetchSummary(periodDays: number): Promise<BotStatistics>
fetchChartData(periodDays: number): Promise<BotChartData[]>

// Usuários e Mensagens
fetchValidatedUsers(limit?: number): Promise<BotUser[]>
fetchFailedUsers(limit?: number): Promise<BotUser[]>
fetchMessageLogs(botId?: number, limit?: number): Promise<BotMessage[]>

// Export
exportCSV(periodDays: number): Promise<void>
```

**BotStatistics Response**:
```typescript
{
  total_bots: number
  active_bots: number
  total_messages_sent: number
  total_messages_received: number
  total_validated: number
  total_failed: number
  total_pending: number
  success_rate: number           // 0-100
  period_days: number
}
```

**Exemplo de Uso**:
```typescript
import { useBotStatistics } from '@/composables/useBotStatistics'

const { fetchSummary, fetchChartData, exportCSV } = useBotStatistics()

// Dashboard
onMounted(async () => {
  const stats = await fetchSummary(7)
  const charts = await fetchChartData(7)
})

// Export
const handleExport = async () => {
  await exportCSV(30)
  // Baixa arquivo CSV
}
```

---

## Sistema de Tipos TypeScript

**Arquivo**: `resources/js/types/telegram.ts`

### Interfaces Principais

```typescript
// ==================== MODELOS ====================

interface TelegramBot {
  id: number
  name: string
  username: string
  bot_token: string
  api_url: string
  api_key: string
  portal_id: number
  group_chat_id?: string
  group_invite_link?: string
  register_url?: string
  description?: string
  debug_mode: boolean
  status: 'active' | 'inactive' | 'paused'
  webhook_configured_at?: string
  webhook_tested_at?: string
  webhook_last_error?: string
  active_flows_count: number
  users_count: number
  messages_count: number
  created_at: string
  updated_at: string
}

interface BotFlow {
  id: number
  bot_id: number
  name: string
  description?: string
  version: number
  status: 'draft' | 'active' | 'archived'
  is_default: boolean
  steps_count: number
  published_at?: string
  created_at: string
  updated_at: string
  steps: BotFlowStep[]
}

interface BotFlowStep {
  id: number
  flow_id: number
  type: 'message' | 'button' | 'input' | 'validation' | 'condition' | 'action'
  order: number
  data: Record<string, any>
  created_at: string
  updated_at: string
}

interface BotUser {
  id: number
  bot_id: number
  email: string
  phone?: string
  first_name?: string
  last_name?: string
  status: 'validated' | 'failed' | 'pending'
  validation_error?: string
  created_at: string
  last_interaction_at?: string
}

interface BotMessage {
  id: number
  bot_id: number
  user_id: number
  content: string
  direction: 'incoming' | 'outgoing'
  status: 'sent' | 'failed' | 'read'
  created_at: string
}

interface BotStatistics {
  total_bots: number
  active_bots: number
  total_messages_sent: number
  total_messages_received: number
  total_validated: number
  total_failed: number
  total_pending: number
  success_rate: number
  period_days: number
}

// ==================== PAYLOADS ====================

interface CreateBotPayload {
  name: string
  bot_token: string
  username: string
  api_url: string
  api_key: string
  portal_id: number
  group_chat_id?: string
  group_invite_link?: string
  register_url?: string
  description?: string
  debug_mode?: boolean
}

interface UpdateBotPayload {
  name?: string
  api_url?: string
  api_key?: string
  portal_id?: number
  group_chat_id?: string
  group_invite_link?: string
  register_url?: string
  description?: string
  debug_mode?: boolean
  status?: 'active' | 'inactive' | 'paused'
}

interface CreateFlowPayload {
  name: string
  description?: string
  is_default?: boolean
  steps: BotFlowStep[]
}

interface UpdateFlowPayload {
  name?: string
  description?: string
  is_default?: boolean
  steps?: BotFlowStep[]
}

// ==================== RESPONSES ====================

interface ApiResponse<T> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

interface ApiListResponse<T> {
  success: boolean
  data: T[]
  meta: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

// ==================== CHARTS ====================

interface BotChartData {
  date: string              // YYYY-MM-DD
  messages: number
  validated: number
  failed: number
}

// ==================== OUTROS ====================

interface WebhookInfo {
  url: string
  configured: boolean
  tested: boolean
  last_error?: string
  last_set_at?: string
  last_tested_at?: string
}

interface FlowBuilderState {
  botId: number
  flowId?: number
  steps: BotFlowStep[]
  selectedStepId?: number
  isDraft: boolean
}
```

---

## Roteamento e Navegação

**Arquivo**: `resources/js/router.ts`

### Rotas Configuradas

```typescript
const routes = [
  {
    path: '/telegram-bots',
    children: [
      // Listagem de bots
      {
        path: '',
        component: ListBots,
        meta: { title: 'Meus Bots' }
      },
      
      // Criar bot
      {
        path: 'create',
        component: CreateEditBot,
        meta: { title: 'Criar Bot' }
      },
      
      // Detalhes do bot
      {
        path: ':id',
        component: BotDetail,
        meta: { title: 'Detalhes do Bot' }
      },
      
      // Editar bot
      {
        path: ':id/edit',
        component: CreateEditBot,
        meta: { title: 'Editar Bot' }
      },
      
      // Fluxos
      {
        path: ':botId/flows',
        component: FlowList,
        meta: { title: 'Fluxos' }
      },
      
      // Novo fluxo
      {
        path: ':botId/flows/new',
        component: FlowBuilder,
        meta: { title: 'Novo Fluxo' }
      },
      
      // Editar fluxo
      {
        path: ':botId/flows/:flowId/edit',
        component: FlowBuilder,
        meta: { title: 'Editar Fluxo' }
      },
      
      // Analytics
      {
        path: 'analytics',
        component: AnalyticsDashboard,
        meta: { title: 'Analytics' }
      },
      
      // Usuários
      {
        path: 'users',
        component: UsersList,
        meta: { title: 'Usuários' }
      }
    ]
  }
]
```

### Exemplo de Navegação

```typescript
// Programática
router.push('/telegram-bots')
router.push({ name: 'bot-detail', params: { id: 123 } })

// Template
<router-link to="/telegram-bots">Bots</router-link>
<router-link :to="`/telegram-bots/${id}`">Detalhes</router-link>
<router-link :to="{ 
  name: 'flow-edit', 
  params: { botId: 1, flowId: 5 } 
}">
  Editar Fluxo
</router-link>
```

---

## Padrões de Design

### 1. Composable Pattern

**Descrição**: Encapsular lógica de estado e API em composables reutilizáveis

**Estrutura**:
```typescript
export const useTelegramBots = () => {
  // Estado
  const bots = ref<TelegramBot[]>([])
  const loading = ref(false)
  
  // Métodos
  const fetchBots = async () => {
    loading.value = true
    try {
      const response = await api.get('/bots')
      bots.value = response.data
    } finally {
      loading.value = false
    }
  }
  
  // Computed
  const activeBots = computed(() => 
    bots.value.filter(b => b.status === 'active')
  )
  
  return { bots, loading, fetchBots, activeBots }
}
```

**Benefícios**:
- Lógica reutilizável entre componentes
- Separação entre lógica e UI
- Testabilidade
- Type safety

---

### 2. Reactive Forms Pattern

**Descrição**: Usar `reactive()` para forms complexos

```typescript
const form = reactive({
  name: '',
  email: '',
  phone: ''
})

const handleSubmit = async () => {
  await api.post('/submit', form)
}

// Template
<input v-model="form.name" />
```

---

### 3. Conditional Rendering Pattern

**Descrição**: Estados previsíveis para UX clara

```typescript
<!-- Loading -->
<div v-if="loading" class="spinner"></div>

<!-- Empty State -->
<div v-else-if="items.length === 0" class="empty-state"></div>

<!-- Error -->
<div v-else-if="error" class="error">{{ error }}</div>

<!-- Content -->
<div v-else>{{ items }}</div>
```

---

### 4. Card/Grid Pattern

**Descrição**: Layout responsivo com TailwindCSS

```typescript
// 1 col móvel, 2 cols tablet, 3 cols desktop
class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"

// Card base
class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition"
```

---

### 5. Badge/Status Pattern

**Descrição**: Indicadores visuais de status

```vue
<span :class="{
  'bg-green-100 text-green-800': status === 'active',
  'bg-red-100 text-red-800': status === 'inactive',
  'bg-yellow-100 text-yellow-800': status === 'paused'
}" class="px-3 py-1 rounded-full text-xs font-medium">
  {{ statusLabel }}
</span>
```

---

## Integração com API

### Configuração Axios

**Arquivo**: `resources/js/api/client.ts` (ou em cada composable)

```typescript
import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Interceptor de autenticação
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor de erro
api.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      // Redirecionar para login
      router.push('/login')
    }
    throw error.response?.data?.message || 'Erro na requisição'
  }
)

export default api
```

### Endpoints Utilizados

```
GET     /api/v1/telegram-bots
POST    /api/v1/telegram-bots
GET     /api/v1/telegram-bots/{id}
PUT     /api/v1/telegram-bots/{id}
DELETE  /api/v1/telegram-bots/{id}
POST    /api/v1/telegram-bots/{id}/webhook/setup
POST    /api/v1/telegram-bots/{id}/webhook/test
POST    /api/v1/telegram-bots/{id}/webhook/reset

GET     /api/v1/telegram-bots/{botId}/flows
POST    /api/v1/telegram-bots/{botId}/flows
GET     /api/v1/flows/{id}
PUT     /api/v1/flows/{id}
DELETE  /api/v1/flows/{id}
POST    /api/v1/flows/{id}/duplicate
POST    /api/v1/flows/{id}/publish

GET     /api/v1/statistics/summary?period=7
GET     /api/v1/statistics/chart?period=7
GET     /api/v1/statistics/validated-users?limit=100
GET     /api/v1/statistics/failed-users?limit=100
POST    /api/v1/statistics/export?period=7
```

---

## Guia de Uso

### Caso 1: Criar um Novo Bot

**Fluxo**:
1. Clique em "+ Novo Bot" (ListBots)
2. Preencha formulário (CreateEditBot)
3. Clique em "Criar"
4. Será redirecionado para detalhes do bot
5. Configure webhook em "Detalhes"

**Campos Necessários**:
- Nome do Bot (ex: "Bot Validação Principal")
- Username (ex: "meu_bot_123")
- Token do Bot (do BotFather)
- API URL (ex: "https://api.base.com/validate")
- API Key (sua chave secreta)
- Portal ID (número)

**Código**:
```typescript
const form = {
  name: 'Bot Validação',
  username: 'bot_validacao',
  bot_token: '123456:ABCdef...',
  api_url: 'https://api.base.com/validate',
  api_key: 'sua-chave-secreta',
  portal_id: 1
}

await createBot(form)
```

---

### Caso 2: Configurar Webhook

**Fluxo**:
1. Vá para Detalhes do Bot (BotDetail)
2. Copie a URL do webhook
3. Clique "Configurar Webhook"
4. Sistema envia para Telegram API
5. Status muda para "Configurado"

**URL do Webhook**:
```
https://seu-dominio.com/api/v1/telegram-bots/{botId}/updates
```

**Código**:
```typescript
const { setupWebhook } = useTelegramBots()

const handleSetup = async () => {
  await setupWebhook(botId)
  // Sistema enviará config para Telegram
}
```

---

### Caso 3: Criar um Fluxo

**Fluxo**:
1. Vá para "Fluxos" do bot
2. Clique "+ Novo Fluxo"
3. Preencha nome (ex: "Welcome Flow")
4. Arraste tipos de passos ao canvas
5. Clique em cada passo para editar propriedades
6. Clique "Salvar Fluxo" (salva como rascunho)
7. Publique quando pronto

**Exemplo - Fluxo de Validação de Email**:
```
Passo 1: Mensagem
├─ "Olá, bem-vindo ao {{ bot.name }}!"

Passo 2: Entrada
├─ "Qual é seu email?"
├─ type: "email"

Passo 3: Validação
├─ POST {{ bot.api_url }}
├─ Field: email

Passo 4: Condição
├─ if status === "valid" → Sucesso
├─ else → Erro, retornar ao passo 2

Passo 5: Ação
├─ Enviar email confirmação
```

**Código**:
```typescript
const flow = {
  name: 'Email Validation',
  description: 'Validate user email',
  steps: [
    { type: 'message', data: { content: 'Hello!' } },
    { type: 'input', data: { content: 'Email?', field_type: 'email' } },
    { type: 'validation', data: { api_endpoint: '/validate' } },
    { type: 'action', data: { action_type: 'email' } }
  ]
}

await createFlow(botId, flow)
```

---

### Caso 4: Ver Analytics

**Fluxo**:
1. Vá para "Analytics"
2. Selecione período (7/30/90 dias)
3. Visualize KPIs principais
4. Analise gráficos de tendência
5. Verifique top 5 bots
6. Clique em bot para detalhes

**Período**: Afeta todos os cálculos automaticamente

---

### Caso 5: Gerenciar Usuários

**Fluxo**:
1. Vá para "Usuários"
2. Selecione aba (Validados/Falhados/Pendentes)
3. Procure por email/telefone
4. Clique "Ver" para detalhes do usuário
5. Visualize histórico de validações

---

## Troubleshooting

### Problema: Bot não carrega

**Possíveis Causas**:
1. Token de autenticação expirado
2. API backend offline
3. Erro de rede

**Solução**:
```typescript
// Verificar autenticação
const token = localStorage.getItem('auth_token')
if (!token) {
  router.push('/login')
}

// Retry com exponential backoff
const retry = async (fn, maxRetries = 3) => {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn()
    } catch (err) {
      if (i === maxRetries - 1) throw err
      await new Promise(r => setTimeout(r, 1000 * Math.pow(2, i)))
    }
  }
}
```

---

### Problema: Formulário não valida

**Possível Causa**: HTML5 validation desabilitada

**Solução**:
```vue
<form @submit.prevent="handleSubmit">
  <!-- required, type, pattern atributos funcionam -->
  <input required type="email" />
  <button type="submit">Submit</button>
</form>
```

---

### Problema: Webhook test falha

**Possíveis Causas**:
1. URL webhook incorreta
2. Servidor não acessível
3. Token do bot inválido
4. Rate limit Telegram

**Solução**:
- Verificar URL do webhook (deve ser HTTPS)
- Verificar logs do servidor
- Aguardar antes de tentar novamente
- Usar modo debug para mais informações

---

### Problema: FlowBuilder lento com muitos passos

**Solução**:
```typescript
// Usar v-show em vez de v-if para passos ocultos
<div v-show="step.visible" :key="step.id">

// Virtualizar lista se > 100 passos
import { VirtualScroller } from 'vue-virtual-scroller'
```

---

## Performance e Otimizações

### 1. Code Splitting

```typescript
// Router-based lazy loading
const FlowBuilder = defineAsyncComponent(() =>
  import('@/pages/TelegramBots/FlowBuilder.vue')
)

// Component lazy loading
const Charts = defineAsyncComponent(() =>
  import('@/components/Charts.vue')
)
```

### 2. Computed vs Methods

```typescript
// ❌ Ruim - recalcula sempre
<div>{{ getTotal() }}</div>

// ✅ Bom - cacheia resultado
const total = computed(() => items.reduce(...))
<div>{{ total }}</div>
```

### 3. Watch com Cleanup

```typescript
const unwatchEffect = watch(() => selectedPeriod.value, async () => {
  await loadData()
}, { immediate: false })

onBeforeUnmount(() => {
  unwatchEffect()  // Cleanup
})
```

### 4. Pagination para Listas Grandes

```typescript
const PAGE_SIZE = 20
const page = ref(1)

const paginatedFlows = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE
  return flows.value.slice(start, start + PAGE_SIZE)
})
```

---

## Conclusão

Esta interface fornece uma experiência completa para gerenciar bots Telegram, criar fluxos complexos e analisar dados em tempo real. O código é type-safe, responsivo e segue padrões Vue 3 modernos.

**Próximos Passos**:
- Implementar testes unitários (Vitest)
- Adicionar E2E tests (Cypress)
- Otimizar bundle size
- Adicionar PWA capabilities
- Implementar real-time updates (WebSockets)

---

**Documentação criada em**: Janeiro 2026  
**Versão do Vue**: 3.x  
**Versão do TypeScript**: 5.x
