# T-08 — Clientes

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/clients`, `/clients/{id}` |
| **Funcionalidades** | F-03 |
| **Acesso** | Mais → Clientes |

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
| CEP | Preenche endereço por consulta de CEP (a definir: serviço externo) |
| Inativar | Só aparece se houver lançamentos; senão "Excluir" (RN-13) |
