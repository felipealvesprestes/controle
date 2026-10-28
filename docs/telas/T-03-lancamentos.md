# T-03 — Lançamentos

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/lancamentos` |
| **Funcionalidades** | F-05 |
| **Acesso** | Barra inferior |

## Propósito

Consultar, filtrar e corrigir lançamentos de qualquer dia.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ Lançamentos                  │
│ [Out 2026 ▾] [WIS ▾] [Todas▾]│
│ 22h04 · 51 lançamentos       │
├──────────────────────────────┤
│ seg, 26/10           2h15    │
│ ● 09:00–10:30  1h30  Dev     │
│   MRP-90 Filtrar empresas…   │
│ ● 14:00–14:45  0h45  Plan    │
│   MRP-91 Ajustar seletor…    │
│                              │
│ sex, 23/10           3h10    │
│ 🔒 ...                        │
│                         (+)  │
├──────────────────────────────┤
│ Hoje  Lanç.  Fatur. Painel ⋯ │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Filtros | Mês, cliente, categoria; busca por texto na descrição/referência. Lembra o último filtro |
| Totais | Horas e quantidade conforme filtro |
| Grupo por dia | Cabeçalho com data e total do dia |
| Item | Toque abre T-04; deslizar para a esquerda: duplicar / excluir |
| 🔒 | Lançamento de período fechado: só leitura (RN-10) |

## Estados

- **Vazio com filtro:** "Nada encontrado" + limpar filtros.
- **Carregando:** rolagem infinita por dia.

## Desktop

Tabela com colunas: data, cliente, categoria, referência, descrição, início, fim, duração, valor.
