# T-02 — Hoje

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/` |
| **Funcionalidades** | F-05, F-06 |
| **Acesso** | Barra inferior (tela inicial) |

## Propósito

Tela principal: iniciar ou parar o cronômetro e ver o que foi feito hoje. Deve resolver 90% do uso diário.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ Hoje · ter, 28/10            │
│ 3h40 · R$ 225,00 (parcial)   │
├──────────────────────────────┤
│ ┌──────────────────────────┐ │
│ │ ● WIS · Desenvolvimento ▾│ │
│ │                          │ │
│ │        01:12:45          │ │
│ │                          │ │
│ │ ┌──────────────────────┐ │ │
│ │ │       ■  Parar       │ │ │
│ │ └──────────────────────┘ │ │
│ │ Ajustar início · Descartar│ │
│ └──────────────────────────┘ │
│                              │
│ Lançamentos de hoje          │
│ ● 09:30–10:15  0h45     ⋯   │
│   MRP-62 Permitir empresas…  │
│ ● 13:10–14:10  1h00     ⋯   │
│   MRP-65 Centralizar usuá…   │
│ ● 16:05–17:40  1h35     ⋯   │
│   MRP-73 Vínculo de nota…    │
│                         (+)  │
├──────────────────────────────┤
│ Hoje  Lanç.  Fatur. Painel ⋯ │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Resumo do dia | Horas e valor parcial de hoje, somando todos os clientes |
| Seletor cliente · categoria | Abre bottom sheet com clientes ativos (mais usados primeiro) e categorias |
| Cronômetro parado | Botão grande "▶ Iniciar"; abaixo, "Repetir último: WIS · MRP-73" |
| Cronômetro rodando | Tempo decorrido calculado do início; "Parar" abre confirmação com descrição e referência |
| Lista do dia | Toque edita (T-04); `⋯` duplica ou exclui |
| `(+)` | Lançamento manual (T-04) |

## Estados

- **Vazio:** "Nenhum lançamento hoje." com o botão Iniciar em destaque.
- **Sem clientes:** "Cadastre seu primeiro cliente" → T-08.
- **Período do mês fechado:** aviso "Outubro de WIS está fechado" ao tentar lançar.

## Desktop

Duas colunas: cronômetro à esquerda, lista do dia à direita.
