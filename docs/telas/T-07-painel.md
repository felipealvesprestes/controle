# T-07 — Painel

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/painel` |
| **Funcionalidades** | F-10, F-14 |
| **Acesso** | Barra inferior |

## Propósito

Visão do mês e da evolução, em uma rolagem.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ Painel · Outubro 2026        │
│ ┌────────────┐┌────────────┐ │
│ │ 22h04      ││ R$ 1.335   │ │
│ │ trabalhadas││ parcial    │ │
│ └────────────┘└────────────┘ │
│ ┌────────────┐┌────────────┐ │
│ │ 16 dias    ││ R$ 1.575   │ │
│ │ 1h23/dia   ││ a receber  │ │
│ └────────────┘└────────────┘ │
│                              │
│ Meta R$ 3.000 (v1.3)         │
│ ████████░░░░░░░  45%         │
│ Previsão: R$ 1.720           │
│                              │
│ Por cliente                  │
│ WIS        ██████████ 22h04  │
│                              │
│ Últimos 12 meses             │
│      ▂  ▅  ▄                 │
│ ...  ago set out             │
│                              │
│ Fator R (v1.3)   18% → Anexo V│
│ Faltam R$ 420/mês de pró-lab.│
├──────────────────────────────┤
│ Hoje  Lanç.  Fatur. Painel ⋯ │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Cards | Horas, valor parcial, dias trabalhados, a receber |
| Seletor de mês | Toque no título troca o mês |
| Gráficos | Barras simples em SVG; toque mostra o valor |
| Fator R | Link para T-10 (pró-labore); sempre com o aviso "estimativa" |

## Desktop

Grade de 4 cards no topo e gráficos lado a lado.
