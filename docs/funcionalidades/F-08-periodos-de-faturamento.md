# F-08 — Períodos de faturamento

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.5 |
| **Telas** | T-05, T-06 |
| **Regras** | RN-06, RN-08, RN-09, RN-10, RN-11 |

## Objetivo

Transformar os lançamentos do mês de um cliente numa cobrança fechada, com um valor que não muda mais e que acompanha a nota até o pagamento.

## Histórias

- Como prestador, quero ver quanto cada cliente me deve no mês, ao vivo.
- Como prestador, quero fechar o mês e ter certeza de que relatório e nota terão o mesmo valor.
- Como prestador, quero registrar o número da nota e depois o pagamento.

## Comportamento

- Lista de períodos agrupada por status: Abertos, Aguardando nota (Fechado), Aguardando pagamento (Faturado), Pagos.
- Período aberto mostra totais "parciais" calculados ao vivo.
- **Fechar:** mostra o resumo (minutos, pontos, arredondamento aplicado, valor) e pede confirmação. Avisa se o mês ainda não terminou.
- **Reabrir:** só sem nota; apaga o snapshot.
- **Registrar nota** (antes da F-12): número, data de emissão, PDF/XML opcionais → Faturado.
- **Registrar pagamento:** data e valor recebido → Pago. Diferença entre valor cobrado e recebido fica visível.

## Critérios de aceite

- [ ] Transições fora do diagrama da RN-09 são impossíveis.
- [ ] O snapshot grava exatamente o que o `BillingCalculator` calculou (RN-11).
- [ ] Mudar taxa, política ou categoria depois do fechamento não altera o período.
- [ ] Os exemplos da RN-06 (agosto a outubro) são testes automatizados.

## Notas técnicas

Casos de uso: `CloseBillingPeriod`, `ReopenBillingPeriod`, `AttachManualInvoice`, `RegisterPayment`, `UndoPayment`. Evento `PeriodClosed` dispara a geração do PDF ([F-09](F-09-relatorio-do-periodo.md)).
