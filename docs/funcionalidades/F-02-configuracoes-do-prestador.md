# F-02 — Configurações do prestador

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.3 |
| **Telas** | T-10 |
| **Regras** | RN-04, RN-06 |

## Objetivo

Guardar os padrões de cobrança e os dados da empresa usados no relatório e na nota.

## Histórias

- Como prestador, quero definir uma taxa padrão para não repetir em cada cliente.
- Como prestador, quero escolher a política de arredondamento padrão.
- Como prestador, quero cadastrar os dados da empresa uma vez para usar no relatório e na nota.

## Comportamento

Seções da tela:

1. **Cobrança:** taxa horária padrão (R$), política de arredondamento padrão (Período, Lançamento, Exato) com exemplo ao vivo ("1h07 → 5 pontos → R$ 75,00").
2. **Empresa:** razão social, nome fantasia, CNPJ, inscrição municipal, endereço, regime tributário.
3. **Nota fiscal** (v1.1): código de tributação nacional, código NBS, CNAE, texto base da descrição da nota com variáveis (`{cliente}`, `{mes}`, `{horas}`).
4. **Certificado** (v1.2): envio do A1, validade e status.

## Critérios de aceite

- [ ] CNPJ validado (dígitos verificadores).
- [ ] Alterar a taxa padrão não altera períodos fechados (RN-11).
- [ ] O exemplo de arredondamento atualiza conforme a política escolhida.

## Notas técnicas

Registro único em `provider_profiles`, criado pelo seeder com a taxa de R$ 60,00 e política Período.
