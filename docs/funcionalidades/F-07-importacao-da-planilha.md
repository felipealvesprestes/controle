# F-07 — Importação da planilha

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.5 |
| **Telas** | T-10 |

## Objetivo

Trazer o histórico de agosto a outubro de 2026 para o sistema, para que o painel e o Fator R tenham dados desde o início.

## Comportamento

- Envio do arquivo `.xlsx` exportado do Google Sheets.
- Escolha do cliente de destino (a planilha atual é toda do cliente WIS).
- Lê as abas com nome de mês (`Agosto 2026`...), linhas a partir da 9: data, categoria, descrição, início, fim.
- Prévia antes de gravar: quantidade de lançamentos por mês, total de minutos, linhas com problema (horário inválido, sobreposição, categoria desconhecida).
- Lançamentos importados recebem `source = import`.
- Opção de marcar meses importados como **Pago** com o valor efetivamente cobrado na época (ex.: agosto R$ 652,50), para não recalcular com a regra nova.

## Critérios de aceite

- [ ] Importar agosto a outubro resulta em 652, 1.564 e 1.324 minutos.
- [ ] Importar o mesmo arquivo duas vezes não duplica lançamentos.
- [ ] Linhas com problema são listadas e não impedem a importação das demais.

## Notas técnicas

`SpreadsheetImporter` em `Infrastructure/Import`. Também disponível por comando: `php artisan app:import-spreadsheet {arquivo} --client=`. Exige biblioteca de leitura de `.xlsx` (dependência a aprovar).
