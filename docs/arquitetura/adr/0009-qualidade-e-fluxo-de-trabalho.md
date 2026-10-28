# ADR-0009 — Qualidade e fluxo de trabalho

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O sistema vai emitir notas fiscais e evoluir por muito tempo. Regras de cálculo erradas custam dinheiro; acoplamento custa manutenção.

## Decisão

- **Cobertura de testes de 100% das linhas** em `app/`, desde o primeiro commit. O CI falha abaixo disso (`pest --coverage --min=100`).
- **PHPStan no nível máximo** (Larastan), sem baseline.
- **Pint** no padrão do projeto; CI roda em modo de verificação.
- **Testes de arquitetura** (Pest `arch()`): `Domain` não depende de Laravel nem de outras camadas; `Application` não depende de `Infrastructure` nem de `Http`/`Livewire`; sem `dd`/`dump`/`ray`; classes de domínio `final` e Value Objects `readonly`.
- **Commits no padrão Conventional Commits**, em português: `tipo: mensagem`. Detalhes em [contribuindo.md](../../contribuindo.md).
- **Componentes Livewire do projeto são baseados em classe** (`app/Livewire`), com a view separada. Componentes de arquivo único (`⚡*.blade.php`) ficam fora do PHPStan e da cobertura; os que vieram do starter kit serão substituídos pelas telas do console.
- Exclusões de cobertura só com justificativa por escrito no próprio código (`@codeCoverageIgnore` + motivo) e revisão.

## Alternativas consideradas

- **Meta de cobertura gradual (ex.: 80%)** — cria dívida que nunca é paga. Começar em 100% é mais barato agora.
- **Mutation testing (Infection)** — complementa bem a cobertura; reavaliar quando o domínio de faturamento estiver completo.

## Consequências

- Cada mudança vem com teste; código difícil de testar é sinal de acoplamento e deve ser redesenhado.
- O driver de cobertura (pcov) precisa estar disponível: no CI via `setup-php`; localmente via Docker (ver [contribuindo.md](../../contribuindo.md)).
