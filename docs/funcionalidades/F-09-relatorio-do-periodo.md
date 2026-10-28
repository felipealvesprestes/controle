# F-09 — Relatório do período

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.5 |
| **Telas** | T-06 |
| **Regras** | RN-06, RN-11, RN-12 |

## Objetivo

Gerar o demonstrativo que acompanha a nota, com valores idênticos ao do período fechado.

## Comportamento

- **PDF:** cabeçalho com prestador e cliente, mês, lista de lançamentos (data, categoria, referência, descrição, início, fim, duração), subtotais por categoria, total de minutos, arredondamento aplicado, pontos, taxa e valor.
- **CSV:** mesmas linhas, para o cliente conferir em planilha.
- Compartilhar pelo menu nativo do celular (Web Share API) ou baixar.
- Período aberto gera relatório com marca d'água "Parcial".

## Critérios de aceite

- [ ] O total do relatório é sempre igual a `billing_periods.amount_cents` (RN-11).
- [ ] Lançamentos não faturáveis aparecem com valor zero (RN-12).
- [ ] Datas e horas em `America/Sao_Paulo`, formato brasileiro.

## Notas técnicas

`PdfPeriodReport` e `CsvPeriodReport` em `Infrastructure/Reports`, atrás de uma interface `PeriodReport`. Biblioteca de PDF a definir em ADR (dependência a aprovar).
