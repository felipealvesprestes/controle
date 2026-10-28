# F-03 — Clientes e taxas

| | |
|---|---|
| **Status** | 📋 Planejado |
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

- Campos: nome curto, razão social, CPF/CNPJ, e-mail, endereço, cor, política de arredondamento (ou "usar padrão"), retém ISS.
- **Taxas:** lista com "a partir de" e valor. "Novo reajuste" pede valor e data de início. A taxa vigente aparece em destaque.
- Cliente sem taxa própria mostra "Padrão: R$ 60,00/h".
- Cliente com lançamentos só pode ser inativado (RN-13).
- Indicador "dados fiscais incompletos" quando faltar algo necessário para a nota.

## Critérios de aceite

- [ ] Não é possível ter duas taxas com a mesma data de início para o mesmo cliente.
- [ ] Reajuste com data dentro de um período fechado é recusado, com explicação.
- [ ] Lançamentos novos usam a taxa vigente na data de início (RN-04).
- [ ] Cliente inativo some das escolhas de novos lançamentos.

## Notas técnicas

Casos de uso: `CreateClient`, `UpdateClient`, `ScheduleRate`, `DeactivateClient`. `RateSchedule` (domínio) resolve a taxa para uma data.
