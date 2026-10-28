# T-01 — Login

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Rota** | `/login`, `/two-factor-challenge` |
| **Funcionalidades** | F-01 |
| **Acesso** | Público |

## Propósito

Entrar no sistema. Vem do starter kit; ajustes: remover link de cadastro, textos em português, marcar "lembrar-me" por padrão.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│                              │
│           [logo]             │
│        Controle de Horas     │
│                              │
│  E-mail                      │
│  ┌────────────────────────┐  │
│  └────────────────────────┘  │
│  Senha                       │
│  ┌────────────────────────┐  │
│  └────────────────────────┘  │
│  [✓] Manter conectado        │
│                              │
│  ┌────────────────────────┐  │
│  │         Entrar         │  │
│  └────────────────────────┘  │
│       Esqueci minha senha    │
└──────────────────────────────┘
```

## Estados

- **Erro:** "E-mail ou senha incorretos." sem dizer qual.
- **Bloqueio:** após 5 tentativas, "Tente novamente em 1 minuto."
- **2FA:** segunda tela com campo de 6 dígitos (`inputmode="numeric"`, `autocomplete="one-time-code"`).
