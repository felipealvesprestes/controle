# ADR-0004 — Dinheiro em centavos e tempo em minutos inteiros

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

A planilha guardava duração como fração de dia e valores como `float`, gerando números como `24.99999999999991`. Em documento fiscal, diferença de centavo é erro.

## Decisão

- Dinheiro: Value Object `Money` com valor inteiro em centavos e moeda BRL. Colunas `*_cents` do tipo inteiro.
- Tempo: Value Object `Minutes` (inteiro, não negativo). Colunas `*_minutes`.
- Pontos: Value Object `Points` (inteiro), criado a partir de `Minutes` por uma `RoundingPolicy`.
- Arredondamento monetário meio para cima, uma única vez no total ([RN-07](../../produto/regras-de-negocio.md#rn-07--representação-de-valores)).
- Formatação (`R$ 1.335,00`, `22h05`) só na camada de interface.

## Alternativas consideradas

- **`decimal` no banco + `bcmath`** — funciona, mas inteiros são mais simples, rápidos e portáveis entre bancos.
- **Biblioteca `moneyphp/money`** — boa, mas é uma dependência para um caso de moeda única. Reavaliar se surgir multi-moeda.

## Consequências

- Nenhum `float` em regra de negócio. Larastan e testes devem barrar.
- Conversões para exibição centralizadas em helpers de formatação.
