# T-06 — Período

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/billing/{id}` |
| **Funcionalidades** | F-08, F-09, F-12 |
| **Acesso** | T-05 |

## Propósito

Conferir, fechar, faturar e registrar o pagamento de um mês de um cliente.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ ← WIS · Outubro 2026         │
│ [ Aberto ]─Fechado─Faturado─Pago
├──────────────────────────────┤
│ Minutos trabalhados   1.324  │
│ Arredondamento (período) +11 │
│ Pontos                   89  │
│ Taxa               R$ 60/h   │
│ ──────────────────────────── │
│ Total          R$ 1.335,00   │
│                  (parcial)   │
├──────────────────────────────┤
│ Por categoria                │
│ Desenvolvimento   16h57      │
│ Planejamento       5h07      │
├──────────────────────────────┤
│ 51 lançamentos            ›  │
│ Relatório  [PDF] [CSV] [↗]   │
├──────────────────────────────┤
│ ┌──────────────────────────┐ │
│ │      Fechar período      │ │
│ └──────────────────────────┘ │
└──────────────────────────────┘
```

## Ação principal por status

| Status | Botão principal | Ações secundárias |
|---|---|---|
| Aberto | Fechar período | — |
| Fechado | Preparar nota (v1.1) / Emitir nota (v1.2) / Registrar nota | Reabrir |
| Faturado | Registrar pagamento | Ver nota (XML/PDF), cancelar nota (v1.2) |
| Pago | — | Desfazer pagamento |

## Elementos

| Elemento | Comportamento |
|---|---|
| Linha do tempo | Mostra o status atual e datas de cada etapa |
| Resumo | Ao vivo quando Aberto ("parcial"); snapshot nos demais (RN-11) |
| Lançamentos | Abre T-03 filtrada por cliente e mês |
| Relatório | PDF, CSV e compartilhar (Web Share API) |
| Fechar | Confirmação com o resumo; avisa se o mês não terminou |
| Preparar nota | Lista de campos com "copiar", link para o Emissor Nacional e envio do XML |

## Estados

- **Nota rejeitada (v1.2):** faixa vermelha com o motivo e "Corrigir e reenviar".
- **Valor do XML diferente do snapshot:** alerta com os dois valores.
