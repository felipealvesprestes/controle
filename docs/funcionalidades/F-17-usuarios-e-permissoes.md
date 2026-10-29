# F-17 — Usuários e permissões

| | |
|---|---|
| **Status** | ✅ Implementada (v0.2) |
| **Versão** | v0.2 |
| **Telas** | T-12, T-13 |
| **Regras** | RN-15 a RN-20 |

## Objetivo

O administrador decide **exatamente** o que cada usuário cadastrado vê e faz: itens de menu, páginas, botões, campos e textos (por exemplo, valores em reais). Decisão técnica: [ADR-0007](../arquitetura/adr/0007-permissoes-granulares.md).

## Histórias

- Como administrador, quero cadastrar um usuário e escolher um perfil de acesso para ele.
- Como administrador, quero criar perfis (ex.: "Colaborador", "Contador") marcando item por item o que o perfil acessa.
- Como administrador, quero liberar ou bloquear uma permissão específica para um usuário, sem criar um perfil novo.
- Como administrador, quero ver o que um usuário enxerga antes de liberar o acesso.
- Como administrador, quero saber quem mudou qual permissão e quando.

## Conceitos

| Conceito | Descrição |
|---|---|
| **Permissão** | Uma chave do catálogo, ex.: `billing.period.close`. Definida no código, nunca no banco |
| **Tipo** | `menu` (item de navegação), `page` (acesso à página), `action` (botão/ação), `field` (campo de formulário), `content` (texto ou dado exibido), `scope` (abrangência dos dados) |
| **Catálogo** | Lista de todas as permissões, montada a partir dos módulos. Cada uma tem rótulo, descrição, tipo, tela e dependências |
| **Perfil** | Conjunto nomeado de permissões |
| **Exceção do usuário** | Permitir ou negar uma permissão para um usuário específico, por cima dos perfis |
| **Administrador** | Usuário com acesso total, que gerencia usuários e perfis |

### Exemplos do catálogo

| Chave | Tipo | Tela | Depende de |
|---|---|---|---|
| `billing.menu` | menu | — | — |
| `billing.period.view` | page | T-05, T-06 | `billing.menu` |
| `billing.period.close` | action | T-06 | `billing.period.view` |
| `billing.period.reopen` | action | T-06 | `billing.period.view` |
| `billing.amount.view` | content | T-05, T-06, T-07 | — |
| `time.entry.create` | action | T-02, T-04 | — |
| `time.entry.scope.all` | scope | T-03 | — (sem ela, só os próprios lançamentos) |
| `client.rate.view` | field | T-08 | `client.view` |
| `client.rate.edit` | field | T-08 | `client.rate.view` |

O catálogo completo é gerado pelo sistema e exibido na tela T-13; esta tabela é ilustrativa.

## Comportamento

- **Usuários (T-12):** lista com nome, e-mail, perfis, status, último acesso. Criar envia convite por e-mail com link para definir senha. Desativar bloqueia o login imediatamente.
- **Perfis (T-13):** árvore do catálogo agrupada por **módulo → tela → tipo de elemento**, com marcação por item ou por grupo. Ao marcar um item, as dependências são marcadas junto (e mostradas).
- **Exceções:** na ficha do usuário, cada permissão mostra o estado efetivo e a origem ("pelo perfil Colaborador", "permitido para este usuário", "negado para este usuário").
- **Conteúdo sem permissão:** textos do tipo `content` são substituídos por `•••` com dica "Sem permissão para ver"; menus, páginas, botões e campos sem permissão não são renderizados.
- **Histórico:** toda mudança em usuário, perfil ou exceção fica registrada (quem, quando, antes, depois).

## Critérios de aceite

- [x] Usuário sem permissão não vê o elemento **e** recebe 403 ao chamar a ação diretamente (RN-18).
- [x] Não é possível desativar ou remover o último administrador ativo (RN-17).
- [x] Negação explícita vence permissão de perfil (RN-16).
- [x] Toda chave usada no código existe no catálogo — verificado por teste automatizado.
- [x] Mudança de permissão vale na próxima requisição do usuário afetado (cache só dentro da requisição).
- [x] Toda permissão nova no código aparece desmarcada em todos os perfis (RN-15): o banco guarda só atribuições.

## Fora de escopo

- **Ver como** (movido para o [backlog](../ROADMAP.md#backlog-sem-versão) em 2026-10-29): pré-visualização somente leitura da navegação e das telas com as permissões do usuário. A aba Permissões da ficha já mostra o estado efetivo e a origem de cada item.
- Multiempresa (vários prestadores independentes no mesmo sistema).
- Permissões por registro individual (ex.: "só o cliente X"). Pode virar o tipo `scope` com parâmetro no futuro.
