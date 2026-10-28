# F-11 — PWA e experiência mobile

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.1 (layout), v1.0 (PWA completo) |
| **Telas** | todas |

## Objetivo

Que o sistema pareça um app instalado no celular.

## Comportamento

- Manifest com nome, ícones, cor de tema, `display: standalone`, atalho "Iniciar cronômetro".
- Service worker com cache dos assets estáticos e página offline amigável.
- Respeita áreas seguras (notch, barra de gestos) com `env(safe-area-inset-*)`.
- Altura com `100dvh`; inputs com fonte ≥ 16 px (sem zoom no iOS); `inputmode` e `type` corretos em valores e horários.
- Sem destaque de toque azul, sem hover "preso" em telas de toque.
- Modo escuro seguindo o sistema.

## Critérios de aceite

- [ ] Instalável no iOS (Safari) e Android (Chrome).
- [ ] Lighthouse: PWA instalável, Acessibilidade ≥ 90.
- [ ] Todas as telas usáveis com uma mão em 390 × 844.

Diretrizes visuais detalhadas em [telas/README.md](../telas/README.md).
