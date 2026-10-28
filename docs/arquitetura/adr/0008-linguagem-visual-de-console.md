# ADR-0008 — Linguagem visual de console

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O produto deve parecer uma plataforma web profissional, no estilo de consoles como o Google Cloud Console, fugindo do visual padrão de dashboards e da "cara de IA". O starter kit vem com a aparência padrão do Flux. O uso no celular continua importante.

## Decisão

- Adotar o modelo de console: barra superior com seletor de contexto e busca, navegação lateral agrupada, páginas de lista com barra de filtro e tabela densa, páginas de detalhe com abas, painel lateral para criar/editar e aba de histórico.
- Tipografia IBM Plex Sans / Plex Mono servida localmente; acento azul petróleo; raios de 4–6 px; separação por bordas.
- Tokens no `@theme` do Tailwind 4; componentes nunca usam cores literais.
- Flux como base de comportamento; componentes próprios em `resources/views/components/ui/` quando o Flux não se adaptar.
- Regras completas e lista do que é proibido em [design/linguagem-visual.md](../../design/linguagem-visual.md).

## Alternativas consideradas

- **Aparência padrão do Flux/starter kit** — rápida, mas idêntica a milhares de apps Laravel.
- **Material Design / clone do GCP** — copiaria identidade de terceiros; a referência é o modelo de interação, não a marca.
- **Dashboard de cards** — o tipo de interface que o produto quer evitar.

## Consequências

- Mais trabalho inicial no `app-shell` e nos componentes base (v0.1), compensado pela consistência das telas seguintes.
- Wireframes de telas seguem o modelo lista → detalhe → painel lateral.
