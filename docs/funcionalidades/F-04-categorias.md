# F-04 — Categorias

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.3 |
| **Telas** | T-09 |
| **Regras** | RN-12, RN-13 |

## Objetivo

Classificar o trabalho e decidir o que é cobrado.

## Comportamento

- Campos: nome, cor, faturável (sim/não), ativa, ordem.
- Seeder cria as categorias da planilha: Desenvolvimento, Reunião, Bugfix, Planejamento, Outros — todas faturáveis.
- Reordenar arrastando (define a ordem na escolha do lançamento).
- Categoria com lançamentos só pode ser inativada.

## Critérios de aceite

- [ ] Nome único.
- [ ] Lançamento de categoria não faturável aparece no relatório com valor zero e não entra no total (RN-12).
- [ ] Mudar uma categoria para não faturável não altera períodos fechados.
