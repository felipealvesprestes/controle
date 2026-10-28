# T-05 — Faturamento

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/faturamento` |
| **Funcionalidades** | F-08 |
| **Acesso** | Barra inferior |

## Propósito

Ver o que cada cliente deve e em que etapa da cobrança está cada mês.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ Faturamento                  │
│ A receber  R$ 1.575,00       │
├──────────────────────────────┤
│ ABERTOS                      │
│ ● WIS · Outubro 2026         │
│   22h04 · R$ 1.335,00 parcial│
│                              │
│ AGUARDANDO NOTA              │
│ (nenhum)                     │
│                              │
│ AGUARDANDO PAGAMENTO         │
│ ● WIS · Setembro 2026        │
│   NF 123 · R$ 1.575,00 · 12d │
│                              │
│ PAGOS                        │
│ ● WIS · Agosto 2026   ✓      │
│   R$ 652,50 · pago 05/09     │
├──────────────────────────────┤
│ Hoje  Lanç.  Fatur. Painel ⋯ │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| A receber | Soma de Fechado + Faturado |
| Grupos | Um por status (RN-09). Pagos mostra os últimos 6, com "ver todos" |
| Item | Toque abre T-06. Em "Aguardando pagamento", mostra dias desde a emissão |

## Estados

- **Vazio:** "Nenhum período ainda. Os períodos aparecem quando você lança horas."

## Desktop

Quadro em colunas (kanban somente leitura) por status.
