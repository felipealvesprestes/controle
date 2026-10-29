# T-08 — Clientes

| | |
|---|---|
| **Status** | ✅ Implementada (v0.3) |
| **Rota** | `/clients`, `/clients/{id}` |
| **Funcionalidades** | F-03 |
| **Acesso** | Navegação → Cadastros → Clientes (`client.menu`); no celular, pela gaveta até a barra inferior da v0.4 |

## Propósito

Cadastrar clientes, suas taxas e dados para a nota.

## Wireframe — lista

```
┌──────────────────────────────┐
│ ← Clientes               (+) │
├──────────────────────────────┤
│ ● WIS                        │
│   R$ 60/h · Período          │
│ ● Cliente B        ⚠ fiscal  │
│   Padrão (R$ 60/h)           │
│                              │
│ Inativos (1)              ›  │
└──────────────────────────────┘
```

## Wireframe — detalhe

```
┌──────────────────────────────┐
│ ← WIS                 Salvar │
├──────────────────────────────┤
│ Nome curto  [WIS           ] │
│ Cor         ● ● ● ● ●        │
│ Arredondam. [Usar padrão  ▾] │
├──────────────────────────────┤
│ Taxas                        │
│ desde 01/08/2026  R$ 60,00 ◀ │
│ + Novo reajuste              │
├──────────────────────────────┤
│ Dados fiscais                │
│ CNPJ        [00.000.000/0…]  │
│ Razão social[             ]  │
│ E-mail      [             ]  │
│ Endereço    [CEP → busca  ]  │
│ [ ] Retém ISS                │
├──────────────────────────────┤
│ Inativar cliente             │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| ⚠ fiscal | Falta dado obrigatório para a nota |
| Taxas | ◀ marca a vigente; reajuste pede valor e data (não pode cair em período fechado) |
| CEP | Preenche logradouro, bairro, cidade e UF pela ViaCEP ([ADR-0013](../arquitetura/adr/0013-busca-de-cep.md)); com CPF, avisa que o CEP vai para a ViaCEP |
| Inativar / Excluir | Na v0.3 os dois aparecem (ainda não há lançamentos). Excluir pede para digitar EXCLUIR. A partir do F-05, Excluir só para cliente sem lançamentos (RN-13) |

## Desktop (v0.3)

Lista em tabela (cliente com cor e razão social, CPF ou CNPJ, taxa vigente, arredondamento, dados fiscais) com abas Ativos e Inativos; "Novo cliente" abre o painel lateral com nome curto, cor e primeira taxa. Detalhe com abas Detalhes e Histórico: à esquerda os cartões Cliente, Dados fiscais e Endereço com um só "Salvar cliente"; à direita o cartão Taxas com a lista e o novo reajuste. Abaixo de 640 px, a lista mostra só cliente e taxa, com "⚠ fiscal" ao lado do nome.

## Estados

| Estado | O que aparece |
|---|---|
| Vazio | "Nenhum cliente cadastrado." com o botão Novo cliente; na aba Inativos, "Nenhum cliente inativo." |
| Sem permissão | Sem `client.view`, o menu some e as rotas respondem 403; sem `client.fiscal.view` ou `client.rate.view`, os valores aparecem como `•••`; sem as permissões de ação, os campos ficam desabilitados e os botões não aparecem |
| Erro | Mensagem no próprio campo (nome em uso, CPF/CNPJ inválido, taxa repetida na mesma data) |
