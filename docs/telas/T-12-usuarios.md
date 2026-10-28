# T-12 — Usuários

| | |
|---|---|
| **Status** | ✅ Implementada (v0.2) |
| **Rota** | `/admin/users`, `/admin/users/{id}` |
| **Funcionalidades** | F-01, F-17 |
| **Acesso** | Navegação → Administração → Usuários (só administradores) |

## Propósito

Convidar, desativar e definir o acesso de cada usuário.

## Wireframe — lista (desktop)

```
Administração › Usuários
Usuários                                        [+ CONVIDAR USUÁRIO] [↻]
┌──────────────────────────────────────────────────────────────────────┐
│ ⏷ Filtro  status: Ativo ✕                                            │
├───┬────────────────┬─────────────────────┬──────────────┬──────────┬──┤
│ ☐ │ Nome           │ E-mail              │ Perfis       │ Status   │  │
├───┼────────────────┼─────────────────────┼──────────────┼──────────┼──┤
│ ☐ │ Felipe Prestes │ felipe@…            │ Administrador│ ● Ativo  │ ⋮│
│ ☐ │ Ana Souza      │ ana@…               │ Colaborador  │ ● Ativo  │ ⋮│
│ ☐ │ Escritório X   │ contato@…           │ Contador     │ ◌ Convite│ ⋮│
└───┴────────────────┴─────────────────────┴──────────────┴──────────┴──┘
```

## Wireframe — detalhe

```
Administração › Usuários › Ana Souza
Ana Souza  ● Ativo                       [VER COMO] [DESATIVAR] [⋮]
[ Detalhes ]  Permissões   Histórico
──────────────────────────────────────────────────────────────────────
Nome            Ana Souza
E-mail          ana@exemplo.com
Perfis          Colaborador  [EDITAR]
Último acesso   28/10/2026 14:02
2FA             Configurado

Aba Permissões
┌─ Faturamento ───────────────────────────────────────────────────────┐
│ Menu Faturamento            ✓ permitida   pelo perfil Colaborador   │
│ Ver períodos                ✓ permitida   pelo perfil Colaborador   │
│ Botão Fechar período        ✕ negada      negada para este usuário  │  [▾]
│ Ver valores em reais        ✕ negada      sem permissão             │  [▾]
└─────────────────────────────────────────────────────────────────────┘
```

## Elementos

| Elemento | Comportamento |
|---|---|
| Convidar | Painel lateral: nome, e-mail, perfis. Envia o convite |
| Ver como | Abre a navegação com as permissões do usuário, em modo somente leitura e com faixa "Visualizando como Ana Souza" |
| `[▾]` em cada permissão | Herdar do perfil · Permitir para este usuário · Negar para este usuário |
| Desativar | Confirmação; bloqueado se for o último administrador ativo (RN-17) |
| Histórico | Registro de auditoria do usuário (RN-20) |

## Celular

Lista em duas linhas (nome + perfil/status). As permissões ficam em seções recolhíveis por módulo.
