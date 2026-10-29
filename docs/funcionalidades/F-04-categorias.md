# F-04 — Categorias

| | |
|---|---|
| **Status** | ✅ Implementada (v0.3) |
| **Versão** | v0.3 |
| **Telas** | T-09 |
| **Regras** | RN-12, RN-13 |

## Objetivo

Classificar o trabalho e decidir o que é cobrado.

## Comportamento

- Campos: nome (único, até 40 caracteres), cor (a mesma paleta dos clientes), faturável (sim/não), ativa e ordem.
- O seeder cria as categorias da planilha, todas faturáveis: Desenvolvimento, Reunião, Bugfix, Planejamento e Outros. Só roda com a tabela vazia, para não trazer de volta uma categoria renomeada ou excluída.
- Reordenar arrastando pela alça (`wire:sort` do Livewire) ou pelos botões subir e descer, que também servem ao teclado. A ordem é a da escolha no lançamento.
- Nova categoria entra no fim da lista. Editar abre o painel lateral com nome, cor e faturável, mais Inativar e Excluir.
- Inativas ficam numa lista à parte, com "Reativar"; a categoria reativada volta no fim da lista.
- Ainda não há lançamentos, então excluir é sempre possível. O F-05 deve fazer `DeleteCategory` recusar categoria com lançamentos, sugerindo inativar (RN-13).

## Critérios de aceite

- [x] Nome único (vale também para dois salvamentos ao mesmo tempo).
- [ ] Lançamento de categoria não faturável aparece no relatório com valor zero e não entra no total (RN-12). O cálculo entra com o relatório do F-09 (v0.5); a marcação já existe.
- [ ] Mudar uma categoria para não faturável não altera períodos fechados. Garantido pelo snapshot do fechamento; o teste entra com o F-08 (v0.5).
- [x] Toda alteração, inclusive a reordenação, fica no histórico de auditoria.

## Segurança

| Ameaça | Controles (`SEC-xx`) | Teste |
|---|---|---|
| Usuário sem permissão cria, altera, reordena, inativa ou exclui pela chamada direta ao Livewire | SEC-02, SEC-46 | `AccessDenied` (403) em cada caso de uso (`CategoriesTest` › permissions) |
| Dois salvamentos com o mesmo nome ao mesmo tempo derrubam a tela com erro 500 | A10 | Violação do índice único vira erro no campo (`CategoriesTest`) |
| Duas reordenações ao mesmo tempo deixam a ordem inconsistente | A06 | Ordem lida com `lockForUpdate` dentro da transação |
| Alteração sem rastro | SEC-41, SEC-47 | Gravação e auditoria na mesma transação |

Permissões do catálogo usadas por esta funcionalidade: `category.menu`, `category.view`, `category.create`, `category.edit` (inclui reordenar e marcar como não faturável), `category.status.edit`, `category.delete`.

## Notas técnicas

Casos de uso em `app/Application/Category`: `SaveCategory` (criar e alterar), `MoveCategory`, `ChangeCategoryStatus` e `DeleteCategory`. A paleta de cores virou o enum `App\Domain\Shared\Color`, compartilhado com os clientes.
