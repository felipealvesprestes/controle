# T-11 — Relatório público

| | |
|---|---|
| **Status** | 💡 Ideia |
| **Rota** | `/r/{token}` |
| **Funcionalidades** | F-13 |
| **Acesso** | Link assinado enviado ao cliente |

## Propósito

O cliente confere as horas do mês sem login.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ [logo prestador]             │
│ Demonstrativo de horas       │
│ WIS · Outubro 2026           │
├──────────────────────────────┤
│ Total  89 pontos · 22h15     │
│        R$ 1.335,00           │
├──────────────────────────────┤
│ 26/10 09:00–10:30 1h30 Dev   │
│ MRP-90 Filtrar empresas…     │
│ ...                          │
├──────────────────────────────┤
│ [Baixar PDF]  [Baixar CSV]   │
│ [ Aprovar ]  (opcional)      │
└──────────────────────────────┘
```

## Estados

- **Link expirado ou revogado:** "Este link não está mais disponível. Fale com o prestador."
- Página sem indexação (`noindex`) e sem navegação do sistema.
