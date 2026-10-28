# T-13 — Perfis de acesso

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/admin/perfis`, `/admin/perfis/{id}` |
| **Funcionalidades** | F-17 |
| **Acesso** | Navegação → Administração → Perfis (só administradores) |

## Propósito

Montar perfis marcando, item por item, os menus, páginas, botões, campos e textos que o perfil libera.

## Wireframe — detalhe do perfil (desktop)

```
Administração › Perfis › Colaborador
Colaborador                                     [SALVAR] [DUPLICAR] [⋮]
[ Permissões ]  Usuários (2)   Histórico
┌──────────────────────────────────────────────────────────────────────┐
│ [ Buscar permissão…              ]   Mostrar: ☑ menu ☑ página        │
│                                              ☑ ação ☑ campo ☑ conteúdo│
├──────────────────────────────────────────────────────────────────────┤
│ ▾ ☑ Lançamentos                                         8 de 9       │
│     ▾ Tela Lançamentos (T-03)                                        │
│        ☑ menu     Item "Lançamentos" na navegação                    │
│        ☑ página   Acessar a lista de lançamentos                     │
│        ☐ escopo   Ver lançamentos de todos os usuários               │
│        ☑ ação     Botão "Duplicar"                                   │
│        ☑ ação     Botão "Excluir"                                    │
│ ▾ ◐ Faturamento                                         2 de 7       │
│     ▾ Tela Período (T-06)                                            │
│        ☑ página   Ver detalhe do período                             │
│        ☐ ação     Botão "Fechar período"   requer: Ver detalhe       │
│        ☐ conteúdo Valores em reais                                   │
│ ▸ ☐ Administração                                       0 de 6       │
└──────────────────────────────────────────────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Árvore | Módulo → tela → permissão. Caixa do grupo marca/desmarca tudo; `◐` = parcial |
| Tipo | Etiqueta em fonte mono (`menu`, `página`, `ação`, `campo`, `conteúdo`, `escopo`) |
| Dependências | Marcar um item marca as dependências e mostra "requer: …"; desmarcar uma dependência avisa o que será desmarcado junto |
| Busca e filtros | Por texto, chave ou tipo |
| Salvar | Mostra resumo da mudança (+3 / −1 permissões, 2 usuários afetados) antes de gravar |
| Usuários | Quem tem o perfil |
| Histórico | Mudanças do perfil (RN-20) |

## Estados

- **Permissões novas no sistema:** faixa "4 permissões novas desde a última revisão" com filtro para vê-las (nascem desmarcadas, RN-15).
- **Perfil com usuários:** excluir pede para mover os usuários para outro perfil.
