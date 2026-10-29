# T-09 — Categorias

| | |
|---|---|
| **Status** | ✅ Implementada (v0.3) |
| **Rota** | `/categories` |
| **Funcionalidades** | F-04 |
| **Acesso** | Navegação → Cadastros → Categorias (`category.menu`) |

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ ← Categorias             (+) │
├──────────────────────────────┤
│ ≡ ● Desenvolvimento          │
│ ≡ ● Reunião                  │
│ ≡ ● Bugfix                   │
│ ≡ ● Planejamento             │
│ ≡ ● Outros                   │
│ ≡ ○ Interno   não faturável  │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| ≡ | Arrastar para reordenar; botões subir e descer fazem o mesmo pelo teclado |
| Item | Toque abre o painel lateral (folha de altura total no celular): nome, cor, faturável, Inativar e Excluir |
| ○ | Categoria não faturável (RN-12) |

## Estados

| Estado | O que aparece |
|---|---|
| Vazio | "Nenhuma categoria ativa. Sem uma, não dá para lançar horas." |
| Inativas | Lista à parte abaixo das ativas, com "Reativar" |
| Sem permissão | Sem `category.view`, o menu some e a rota responde 403; sem `category.edit`, a lista fica só para leitura, sem alça nem botões |
| Erro | Mensagem no campo (nome vazio ou em uso) |
