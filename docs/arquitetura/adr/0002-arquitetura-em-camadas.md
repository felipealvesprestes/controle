# ADR-0002 — Domínio isolado em camadas

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O sistema vai crescer até emitir notas fiscais, com regras tributárias que mudam por lei (IBS/CBS em 2027) e integração com sistemas externos. O cálculo de cobrança já falhou na planilha por estar espalhado. É preciso desacoplar regras de negócio de framework e de integrações, seguindo SOLID.

## Decisão

Criar três pastas novas em `app/`: `Domain`, `Application` e `Infrastructure`, conforme [visao-geral.md](../visao-geral.md).

- `Domain` é PHP puro e não depende do Laravel.
- Toda escrita passa por um caso de uso em `Application`.
- Integrações externas são interfaces no `Domain` com implementação em `Infrastructure`.
- A regra de dependência é verificada por teste de arquitetura do Pest no CI.

## Alternativas consideradas

- **Laravel tradicional (lógica em models e controllers)** — mais rápido no início, mas repete o problema da planilha: regra espalhada e difícil de testar.
- **Módulos por contexto (`app/Modules/Billing/{Domain,Application,...}`)** — melhor para equipes grandes; excesso de estrutura para o tamanho atual. Pode ser adotado depois sem reescrever o domínio.

## Consequências

- Mais classes e mapeamento entre entidades de domínio e models Eloquent.
- Telas de leitura simples (listas, filtros) podem consultar models diretamente, para evitar cerimônia.
- Domínio testável em milissegundos, sem banco.
