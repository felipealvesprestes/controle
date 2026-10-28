# Linguagem visual

Decisão: [ADR-0008](../arquitetura/adr/0008-linguagem-visual-de-console.md).

O sistema tem cara de **console de plataforma**, na linha do Google Cloud Console: denso, sóbrio, orientado a recursos e ações, com dados em primeiro plano. Não é um "dashboard" de cards coloridos, nem uma landing page dentro do app.

A referência é o **modelo de interação** de consoles como o do GCP, não a aparência. Nada de copiar logotipos, cores ou fontes do Google.

## Princípios

1. **Dados antes de decoração.** A tela mostra a informação útil acima da dobra. Nenhum elemento existe só para enfeitar.
2. **Recurso → lista → detalhe → ação.** Toda área segue o mesmo modelo: uma lista filtrável de recursos, uma página de detalhe com abas e uma barra de ações no topo.
3. **Ações explícitas.** Botões dizem o que fazem ("Fechar período", não "Continuar"). Ações destrutivas pedem confirmação digitando o nome do recurso quando o efeito for grave.
4. **Densidade com respiro.** Tabelas compactas, alinhamento rigoroso, espaços consistentes. Densidade não é aperto.
5. **Estado sempre visível.** Status com ícone + texto, nunca só cor. Valores parciais marcados como parciais.
6. **O que a pessoa não pode usar, ela não vê.** Menus, botões, campos e textos respeitam as permissões ([F-17](../funcionalidades/F-17-usuarios-e-permissoes.md)).

## Estrutura da tela (desktop ≥ 1024 px)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ ≡  Controle   [ Cliente: WIS ▾ ]   [ Buscar recursos  /         ]  ⏱ 01:12  ? ◉ │  barra superior (48 px)
├───────────────┬────────────────────────────────────────────────────────────┤
│ ◷ Hoje        │ Faturamento › Períodos                                     │  breadcrumb
│ ☰ Lançamentos │ Períodos                     [+ FECHAR PERÍODO] [↻] [⋮]    │  título + barra de ações
│ ▤ Faturamento │ ┌──────────────────────────────────────────────────────┐   │
│ ◫ Painel      │ │ ⏷ Filtro  status: Aberto ✕   cliente: WIS ✕          │   │  barra de filtro (chips)
│ ───────────── │ ├────┬───────────┬──────────┬──────────┬─────────┬─────┤   │
│ CADASTROS     │ │ ☐  │ Período   │ Cliente  │ Status   │ Pontos  │ Valor│  │
│ ◎ Clientes    │ │ ☐  │ out/2026  │ WIS      │ ● Aberto │      89 │ 1.335│  │  tabela densa (36 px/linha)
│ ◇ Categorias  │ │ ☐  │ set/2026  │ WIS      │ ✓ Pago   │     105 │ 1.575│  │
│ ───────────── │ └────┴───────────┴──────────┴──────────┴─────────┴─────┘   │
│ ADMINISTRAÇÃO │   Linhas por página 25 ▾        1–2 de 2      ‹  ›          │
│ ◉ Usuários    │                                                             │
│ ⚿ Perfis      │                                       ┌──────────────────┐ │
│ ⚙ Configurações│                                      │ painel lateral   │ │  formulários e
│               │                                       │ (criar / editar) │ │  detalhes rápidos
│ « Recolher    │                                       └──────────────────┘ │
└───────────────┴────────────────────────────────────────────────────────────┘
```

| Região | Comportamento |
|---|---|
| **Barra superior** | Menu, nome do produto, **seletor de contexto** (cliente atual ou "Todos", como o seletor de projeto do GCP), busca global com atalho `/`, status do cronômetro, ajuda, menu do usuário |
| **Navegação lateral** | Grupos com título em texto normal ("Trabalho", "Conta"); item ativo num fundo suave do destaque com o texto no destaque, arredondado só à direita, como no GCP. Só aparecem telas que existem e que o usuário pode acessar; vira gaveta abaixo de 1024 px |
| **Cabeçalho da página** | Faixa branca e compacta separada por uma linha: caminho em 13 px, título em 22 px/500, uma linha de contexto, ações à direita e abas sublinhadas, como no console do GCP. Ações em texto com só a inicial maiúscula ("Fechar período") |
| **Barra de filtro** | Campo único que vira chips (`status: Aberto`). Filtros ficam na URL |
| **Tabela** | Cabeçalho fixo, ordenação, seleção múltipla com ações em lote, seletor de colunas, números alinhados à direita com `tabular-nums` |
| **Página de detalhe** | Cabeçalho com nome + status, abas (`Detalhes · Lançamentos · Relatório · Nota fiscal · Histórico`) |
| **Painel lateral** | Abre à direita para criar/editar sem perder a lista. Rodapé fixo com `SALVAR` / `CANCELAR` |
| **Histórico** | Toda página de detalhe tem aba com o registro de auditoria do recurso |

## Referência e ajustes

Referência visual: telas atuais do Google Cloud (barra branca, cabeçalho compacto com abas, menu lateral com item ativo suave, conteúdo em painéis brancos sobre fundo cinza). Ajustes vindos da skill `design-taste-frontend`, aceitos em 2026-10-28:

1. Ações e botões só com a inicial maiúscula, nunca em caixa alta.
2. O destaque vai para dados, para o lugar ativo (menu, aba) e para **uma** ação principal por tela; os demais botões são contornados.
3. Nada de cards de indicador com ícone em círculo, gradiente, emoji ou saudação: o Painel mostra a configuração inicial real e a regra de cobrança calculada pelo domínio.

## Celular (< 768 px)

- Barra superior compacta: menu, contexto e cronômetro.
- Navegação lateral vira gaveta aberta pelo botão de menu. A barra inferior com Hoje, Lançamentos, Faturamento e Painel entra na v0.4, quando essas telas existirem.
- Tabelas viram listas de duas linhas (título + metadados), com as mesmas ações no `⋮`.
- Painel lateral vira folha inferior (bottom sheet) de altura total.
- Ação principal fixa no rodapé, ao alcance do polegar.

## Tokens

Definidos como variáveis CSS no `@theme` do Tailwind 4 (`resources/css/app.css`). Componentes usam só tokens, nunca cores literais.

### Tipografia

| Uso | Fonte | Tamanho / peso |
|---|---|---|
| Interface | **IBM Plex Sans** | 14 px / 400 corpo, 500 rótulos |
| Títulos de página | IBM Plex Sans | 24–28 px / 400 |
| Seções | IBM Plex Sans | 16 px / 500 |
| Códigos, IDs, referências (`MRP-62`), horários | **IBM Plex Mono** | 13 px / 400 |
| Ações e botões | IBM Plex Sans | 14 px / 500, só a inicial maiúscula |

Fontes servidas localmente pelo pacote `@fontsource` (sem CDN, nem no build). Inputs com 16 px no celular para não haver zoom no iOS.

### Cores (tema claro)

| Token | Valor | Uso |
|---|---|---|
| `--color-surface` | `#ffffff` | Conteúdo |
| `--color-canvas` | `#f6f7f8` | Fundo da aplicação, navegação |
| `--color-line` | `#dfe2e6` | Bordas e divisórias (1 px) |
| `--color-ink` | `#1d2024` | Texto principal |
| `--color-ink-muted` | `#5b6370` | Texto secundário, rótulos |
| `--color-accent` | `#0f6b74` | Ações primárias, links, item ativo (azul petróleo) |
| `--color-accent-soft` | `#e3f1f2` | Fundo do item ativo e seleção |
| `--color-ok` | `#1e7a3c` | Pago, autorizado |
| `--color-warn` | `#a35f00` | Pendente, parcial |
| `--color-danger` | `#b3261e` | Erro, rejeitado, ação destrutiva |

Tema escuro: mesmos tokens com valores próprios, fundo `#15171a`, superfície `#1c1f23`, linha `#2c3036`, acento `#4fb3bb`.

### Forma e espaço

- Raio: **6–8 px** em controles e painéis; pílulas totalmente arredondadas só em selos e no item ativo do menu (lado direito).
- Sombra só em camadas sobrepostas (menus, painel lateral, diálogos). Conteúdo é separado por borda, não por sombra.
- Grade de 4 px. Linha de tabela: 36 px (desktop), 48 px (toque).
- Ícones de traço fino (1,5 px), 18 px, sempre com rótulo visível ou `aria-label`.

## Contra a "cara de IA"

Proibido na interface:

- Gradientes decorativos (roxo, índigo, "aurora"), glassmorphism, brilhos e blur de fundo.
- Grade de cards de KPI com ícone dentro de círculo colorido.
- Emojis na interface e na microcopy.
- Saudações e frases vazias ("Bem-vindo de volta!", "Tudo pronto para turbinar sua produtividade").
- Ilustrações genéricas em estados vazios. Estado vazio = frase objetiva + ação + link para a documentação.
- Cantos de 16 px+, sombras grandes, tudo centralizado.
- Fonte Inter com acento índigo padrão do Tailwind.
- Ícone decorativo ao lado de todo título.

Obrigatório:

- Microcopy específica e curta ("89 pontos · arredondado para cima no período").
- Números formatados no padrão brasileiro e alinhados.
- Hierarquia por peso e tamanho tipográfico, não por cor.
- Cada tela revisada com as skills de design do projeto (`impeccable`, `design-taste-frontend`) antes de ser dada como entregue.

## Componentes

Flux é a base de comportamento (acessibilidade, teclado, modais). A aparência vem dos tokens. Componentes que não aceitarem o estilo viram componentes Blade próprios em `resources/views/components/ui/`, documentados aqui:

| Componente | Status |
|---|---|
| Layout `layouts/app/console` (barra superior + navegação + conteúdo) | ✅ v0.1 |
| `ui.nav-item`, `ui.tab` | ✅ v0.1 |
| `ui.page-header` (breadcrumb, título, ações) | ✅ v0.1 |
| `ui.filter-bar` | 📋 Planejado |
| `ui.data-table` | 📋 Planejado |
| `ui.side-panel` | 📋 Planejado |
| `ui.status` (ícone + texto) | 📋 Planejado |
| `ui.key-value` (lista de propriedades do detalhe) | 📋 Planejado |
| `ui.empty-state` | 📋 Planejado |
| `ui.guarded` (renderiza só com permissão) | 📋 Planejado |
