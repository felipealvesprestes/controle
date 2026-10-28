# F-10 — Painel

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.5 (base), v1.3 (meta e previsão) |
| **Telas** | T-07 |

## Objetivo

Responder rápido: quanto já trabalhei e vou receber neste mês, e como estou em relação aos meses anteriores.

## Comportamento

- **Mês atual:** horas, valor parcial, quantidade de dias trabalhados, média por dia.
- **A receber:** soma de períodos fechados e faturados não pagos.
- **Por cliente** e **por categoria** no mês (barras horizontais).
- **Últimos 12 meses:** valor faturado por mês (colunas).
- **v1.3:** meta mensal configurável, barra de progresso e previsão de fechamento com base no ritmo do mês.

## Critérios de aceite

- [ ] Valores de meses fechados vêm do snapshot; o mês aberto é marcado como parcial.
- [ ] Carrega em menos de 1 s com 2 anos de dados.
