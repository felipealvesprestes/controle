# ADR-0005 — Snapshot dos totais no fechamento do período

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

Na planilha, o relatório e a aba do mês calculavam o total de formas diferentes, e o valor enviado ao cliente não batia com o cobrado. Além disso, taxa e política de arredondamento podem mudar com o tempo.

## Decisão

Ao fechar um período, o caso de uso `CloseBillingPeriod` grava no próprio `billing_periods` os minutos faturáveis, minutos de arredondamento, pontos, política, valor e data ([RN-11](../../produto/regras-de-negocio.md#rn-11--snapshot-do-fechamento)). Cada lançamento já guarda a taxa usada (`rate_cents`).

Relatório, exportação e NFS-e leem apenas o snapshot. Períodos abertos mostram o cálculo ao vivo, identificado como "parcial".

## Alternativas consideradas

- **Sempre recalcular** — simples, mas uma mudança de taxa ou de regra alteraria períodos já cobrados.
- **Event sourcing** — histórico completo, porém complexo demais para o caso.

## Consequências

- Reabrir um período apaga o snapshot; fechar de novo grava outro.
- Mudança de regra de cálculo nunca afeta notas emitidas.
