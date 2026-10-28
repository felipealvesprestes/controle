# F-14 — Fator R e estimativa de impostos

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v1.3 |
| **Telas** | T-07, T-10 |

## Objetivo

Acompanhar o Fator R mês a mês e saber quanto do faturamento vira imposto, para conversar com o contador com antecedência. Regras e fontes em [fiscal/simples-nacional.md](../fiscal/simples-nacional.md).

## Comportamento

- Cadastro mensal de pró-labore, folha e receitas fora do sistema.
- Card no painel: Fator R dos últimos 12 meses, anexo resultante (III ou V), pró-labore necessário para chegar a 28%.
- Estimativa do DAS do mês e do valor líquido.
- Aviso claro: "Estimativa. Confirme com seu contador."

## Critérios de aceite

- [ ] Receita mensal vem de períodos faturados, pela data de emissão da nota.
- [ ] Tabelas do Simples versionadas por ano; mudança de tabela não altera estimativas de meses anteriores.

## Notas técnicas

`FactorRCalculator` e `SimplesTaxTable` no domínio (`Domain/Tax`).
