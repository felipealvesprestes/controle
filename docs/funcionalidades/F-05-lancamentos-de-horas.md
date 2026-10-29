# F-05 — Lançamentos de horas

| | |
|---|---|
| **Status** | ✅ Entregue (v0.4) |
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
- Ações na lista: editar (toque no horário), duplicar, excluir (com desfazer por 5 s). Desfazer grava o lançamento de novo para o mesmo dono, passando por todas as regras.
- Lançamento de período não aberto aparece com cadeado e sem ações (RN-10).
- Autocompletar descrição e referência a partir dos lançamentos anteriores do cliente (`datalist` com os últimos 100); escolher uma referência já usada preenche a descrição vazia.
- Edição mantém o cliente ou a categoria do lançamento mesmo inativos; trocar exige um ativo (RN-13).
- Mover o lançamento para outro mês muda o período; os dois precisam estar abertos (RN-10).
- Cliente ou categoria com lançamentos não pode ser excluído, só inativado (RN-13).

## Critérios de aceite

- [x] 23:00 → 01:00 gera 120 min no dia do início.
- [x] Duração > 12 h é recusada; > 4 h pede confirmação.
- [x] Sobreposição é recusada; encostar (fim = início do outro) é aceito.
- [x] O lançamento grava a taxa vigente (RN-04) e é associado ao período do mês, criado se não existir (RN-08).
- [x] Não é possível criar, editar ou excluir em período fechado.

## Notas técnicas

## Segurança

| Ameaça | Controles (`SEC-xx`) | Teste |
|---|---|---|
| Ler, alterar, duplicar ou excluir lançamento de outro usuário trocando o ID (IDOR) | SEC-03 | Sem `time.entry.scope.all`, o lançamento de outra pessoa responde "não existe mais" (`Security/TimeEntryScopeTest`) |
| Navegador troca o lançamento em edição ou forja o "desfazer" | SEC-03, SEC-46 | `editingId` e a cópia do excluído são `#[Locked]`; o desfazer reautoriza e revalida tudo |
| Lançar, alterar ou excluir em período fechado (adulteração) | SEC-29, RN-10 | `EntryRules` recusa em todos os casos de uso (`TimeEntriesTest`) |
| Dois salvamentos ao mesmo tempo passam pela checagem de sobreposição | A06 | Transação com trava na linha do dono (`lockOwner`) antes de checar e gravar |
| Primeiro lançamento do mês salvo duas vezes ao mesmo tempo | A10 | Índice único `client_id + month` com `createOrFirst` |
| Usuário sem permissão age pela chamada direta ao Livewire | SEC-02, SEC-46 | `AccessDenied` (403) em cada caso de uso |
| Valor exposto a quem não pode ver | SEC-01 | `time.entry.amount.view` esconde valores em T-02, T-03 e T-04 |
| Alteração sem rastro | SEC-41 | Criar, alterar, excluir e restaurar gravam auditoria na mesma transação |

Permissões do catálogo: `time.entry.menu`, `time.entry.view`, `time.entry.amount.view`, `time.entry.create`, `time.entry.edit`, `time.entry.delete`, `time.entry.scope.all`.

## Notas técnicas

Casos de uso: `LogTimeEntry`, `UpdateTimeEntry`, `DeleteTimeEntry`, `RestoreTimeEntry`. As regras comuns ficam em `Application/Time/EntryRules`; a sobreposição é consultada pelo `TimeEntryRepository::firstOverlapping` (não há `OverlapChecker` separado). Datas são digitadas e agrupadas no fuso de exibição; o banco guarda UTC. O período nasce só com cliente, mês e status; as colunas do snapshot chegam com o F-08.
