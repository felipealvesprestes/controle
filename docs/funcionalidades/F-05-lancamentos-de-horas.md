# F-05 — Lançamentos de horas

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.4 |
| **Telas** | T-02, T-03, T-04 |
| **Regras** | RN-02, RN-03, RN-04, RN-08, RN-10 |

## Objetivo

Registrar trabalho em poucos segundos, sem erros de horário e sem precisar criar mês.

## Histórias

- Como prestador, quero lançar um intervalo que já aconteceu informando início e fim.
- Como prestador, quero repetir o último lançamento (mesmo cliente, categoria, descrição e referência) mudando só os horários.
- Como prestador, quero filtrar meus lançamentos por cliente, categoria e mês.

## Comportamento

- Formulário: cliente, categoria, descrição, referência (opcional), data, início, fim. A duração aparece ao vivo.
- Padrões inteligentes: último cliente e categoria usados; data de hoje; início = fim do último lançamento do dia.
- Fim menor que início sugere "terminou no dia seguinte?" (RN-02).
- Sobreposição mostra o lançamento conflitante (RN-03).
- Ações na lista: editar, duplicar, excluir (com desfazer por 5 s).
- Lançamento de período não aberto aparece com cadeado e sem ações (RN-10).
- Autocompletar descrição e referência a partir dos lançamentos anteriores do cliente.

## Critérios de aceite

- [ ] 23:00 → 01:00 gera 120 min no dia do início.
- [ ] Duração > 12 h é recusada; > 4 h pede confirmação.
- [ ] Sobreposição é recusada; encostar (fim = início do outro) é aceito.
- [ ] O lançamento grava a taxa vigente (RN-04) e é associado ao período do mês, criado se não existir (RN-08).
- [ ] Não é possível criar, editar ou excluir em período fechado.

## Notas técnicas

Casos de uso: `LogTimeEntry`, `UpdateTimeEntry`, `DeleteTimeEntry`. `OverlapChecker` no domínio, consulta via repositório.
