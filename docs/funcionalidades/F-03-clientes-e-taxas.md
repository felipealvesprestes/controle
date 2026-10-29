# F-03 — Clientes e taxas

| | |
|---|---|
| **Status** | ✅ Implementada (v0.3) |
| **Versão** | v0.3 |
| **Telas** | T-08 |
| **Regras** | RN-04, RN-06, RN-13 |

## Objetivo

Cadastrar quem recebe a cobrança, com taxa e política próprias e dados suficientes para a nota.

## Histórias

- Como prestador, quero cadastrar um cliente com nome curto e dados fiscais.
- Como prestador, quero programar um reajuste de taxa a partir de uma data sem afetar o que já foi cobrado.
- Como prestador, quero inativar um cliente antigo sem perder o histórico.

## Comportamento

- **Cadastro rápido** (painel lateral da lista): nome curto, cor e, opcionalmente, a primeira taxa com a data de início. Taxa vazia usa o padrão do prestador. Ao cadastrar, abre a página do cliente.
- **Página do cliente:** nome curto (único, até 60 caracteres), cor (8 opções), política de arredondamento ("Usar o padrão" ou uma das três da RN-06), CPF ou CNPJ, razão social ou nome completo, e-mail, retém ISS e endereço com busca pelo CEP ([ADR-0013](../arquitetura/adr/0013-busca-de-cep.md)).
- **CPF ou CNPJ** no mesmo campo: 11 dígitos são lidos como CPF; o resto precisa ser um CNPJ válido, numérico ou alfanumérico. Ambos com dígitos verificadores.
- **Taxas:** lista com "desde" e valor, a mais nova primeiro; a vigente hoje aparece em destaque. "Novo reajuste" pede valor e data de início. Uma taxa digitada errado pode ser removida (fica no histórico). Os períodos fechados não mudam, porque guardam o próprio snapshot (RN-11).
- Cliente sem taxa própria mostra "Padrão: R$ 60,00/h"; com taxas só no futuro, mostra o padrão até a primeira começar.
- **Dados fiscais incompletos:** aviso na lista e na página quando falta razão social, CPF/CNPJ ou endereço completo (logradouro, número, bairro, cidade, UF e CEP; complemento é opcional). O aviso diz o que falta.
- **Ativos e inativos** em abas na lista. Inativar tira o cliente das escolhas de novos lançamentos e mantém o histórico (RN-13). Excluir pede para digitar EXCLUIR e apaga o cliente com as taxas.
- O passo "Primeiro cliente e taxa" do Painel fica feito quando há um cliente ativo.

## Critérios de aceite

- [x] Não é possível ter duas taxas com a mesma data de início para o mesmo cliente (índice único; vale também para dois salvamentos ao mesmo tempo).
- [ ] Reajuste com data dentro de um período fechado é recusado, com explicação. Entra com o F-08 (v0.5), quando os períodos existirem.
- [x] `RateSchedule` resolve a taxa vigente numa data (RN-04). Os lançamentos passam a usá-la no F-05 (v0.4).
- [ ] Cliente inativo some das escolhas de novos lançamentos. A escolha de cliente nasce no F-05 (v0.4) e deve listar só ativos.
- [x] Toda alteração fica no histórico do cliente (quem, quando, antes, depois).

## Segurança

| Ameaça | Controles (`SEC-xx`) | Teste |
|---|---|---|
| Usuário sem permissão cria, altera, reajusta, inativa ou exclui pela chamada direta ao Livewire | SEC-02, SEC-46 | `AccessDenied` (403) em cada caso de uso (`ClientsTest` › permissions) |
| CPF/CNPJ, razão social ou nome, e-mail, endereço ou taxa vazarem pelo HTML ou pelo estado do Livewire | SEC-04, F-17 | Sem `client.fiscal.view`, as propriedades ficam vazias e a lista mostra `•••` e omite a razão social (A-14, `AuditRunThreeTest`); sem `client.rate.view`, a taxa não é renderizada |
| Remover a taxa de outro cliente trocando o id | SEC-02 (IDOR) | Remoção filtra por cliente; teste "does not remove a rate of another client" |
| Campos extras ou adulterados (cor, política) gravados | SEC-05 | Repositório grava só os campos do objeto de domínio, depois de validados no caso de uso |
| Dois cadastros com o mesmo nome ao mesmo tempo derrubam a tela com erro 500 | A10 | `tests/Feature/Security/ClientInputTest.php` (A-12) |
| Alteração sem rastro | SEC-41, SEC-47 | Gravação e auditoria na mesma transação |
| Busca de CEP como proxy | SEC-30, SEC-48 | Reaproveita `LookUpZipCode` com a permissão `client.edit` |

Permissões do catálogo usadas por esta funcionalidade: `client.menu`, `client.view`, `client.fiscal.view`, `client.rate.view`, `client.create`, `client.edit` (depende de `client.fiscal.view`), `client.rate.edit`, `client.status.edit`, `client.delete`.

## Privacidade

- Cliente pessoa física tem CPF, nome, e-mail e endereço, que são dados pessoais (LGPD). Ficam visíveis só com `client.fiscal.view`.
- O CEP digitado é enviado à ViaCEP para preencher o endereço, inclusive o de pessoas físicas; a página do cliente avisa isso quando o documento é um CPF ([ADR-0013](../arquitetura/adr/0013-busca-de-cep.md)).
- Ao excluir um cliente, o histórico guarda só o nome. As alterações anteriores continuam no histórico com os valores antigos, porque a auditoria é só de inclusão (RN-20). Um pedido de eliminação de dados pela LGPD precisa de um procedimento próprio, a definir antes da v1.0.

## Notas técnicas

Casos de uso em `app/Application/Client`: `CreateClient`, `UpdateClient`, `ScheduleRate`, `RemoveRate`, `ChangeClientStatus` (inativar e reativar) e `DeleteClient`. `RateSchedule` (domínio) resolve a taxa para uma data. O documento é o value object `TaxId`, que envolve `Cpf` ou `Cnpj` (`app/Domain/Shared`). O endereço é o value object `Address` (`app/Domain/Address`), reaproveitado pelos dados da empresa do F-02, assim como o componente `ui.address-fields` e a busca de CEP (`FillsAddressFromZipCode`).

Ainda não há lançamentos, então excluir é sempre possível. O F-05 deve fazer `DeleteClient` recusar cliente com lançamentos, sugerindo inativar (RN-13), e a T-08 passa a mostrar "Excluir" só para cliente sem lançamentos.
