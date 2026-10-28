# T-09 — Categorias

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/categories` |
| **Funcionalidades** | F-04 |
| **Acesso** | Mais → Categorias |

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
| ≡ | Arrastar para reordenar |
| Item | Toque abre bottom sheet: nome, cor, faturável, ativa |
| ○ | Categoria não faturável (RN-12) |
