# T-04 — Lançamento (criar/editar)

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/time-entries/create`, `/time-entries/{id}` (bottom sheet sobre a tela de origem) |
| **Funcionalidades** | F-05, F-16 |
| **Acesso** | `(+)` em T-02/T-03, toque num lançamento |

## Propósito

Registrar ou corrigir um intervalo de trabalho.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ ───                          │
│ Novo lançamento          ✕   │
│                              │
│ Cliente      [● WIS       ▾] │
│ Categoria    [Desenvolvim.▾] │
│ Referência   [MRP-62       ] │
│ Descrição                    │
│ [Permitir empresas inativas ]│
│ [nos vínculos de usuários   ]│
│                              │
│ Data         [ter, 28/10  ▾] │
│ Início [09:30]   Fim [10:15] │
│                              │
│ Duração 0h45 · 3 pts · R$ 45 │
│                              │
│ ┌──────────────────────────┐ │
│ │         Salvar           │ │
│ └──────────────────────────┘ │
└──────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Cliente / Categoria | Pré-preenchidos com os últimos usados |
| Referência | Autocompleta referências anteriores do cliente; com F-16, busca o título no Linear |
| Descrição | Autocompleta a partir da referência |
| Início / Fim | `type="time"`; início sugerido = fim do último lançamento do dia |
| Linha de duração | Atualiza ao vivo. Pontos e valor são informativos (o arredondamento real é do período) |
| Fim < início | Pergunta "Terminou no dia seguinte?" (RN-02) |

## Estados

- **Erro de sobreposição:** mostra o lançamento conflitante com link.
- **Duração > 4 h:** confirmação. **> 12 h:** bloqueado.
- **Edição em período fechado:** campos desabilitados e aviso com link para reabrir o período.

## Desktop

Modal centralizado, mesmos campos em duas colunas.
