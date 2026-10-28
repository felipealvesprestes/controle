# ADR-0012 — MySQL como banco oficial e Sail no desenvolvimento

| | |
|---|---|
| **Status** | Aceita (substitui [ADR-0003](0003-sqlite-com-litestream.md)) |
| **Data** | 2026-10-28 |

## Contexto

O sistema passou a ter vários usuários com permissões (F-17) e vai guardar dados fiscais. O ambiente de desenvolvimento deve ser igual ao de produção, para que testes e consultas se comportem da mesma forma, e o projeto já traz o Laravel Sail configurado.

## Decisão

- **Banco oficial: MySQL 8.4** em todos os ambientes.
- **Desenvolvimento com Laravel Sail** (`compose.yaml`): `laravel.test` (PHP 8.5), `mysql` e `mailpit`. Todas as portas publicadas só em `127.0.0.1`. O MinIO do Sail foi removido por não ser usado.
- **Testes contra MySQL**, no banco `testing` que o Sail cria automaticamente; no CI, um serviço `mysql:8.4` no GitHub Actions. Nada de SQLite em memória, para não mascarar diferenças de comportamento.
- **Produção: MySQL gerenciado da Digital Ocean** (backups diários e recuperação em ponto no tempo inclusos), em rede privada (VPC) com o Droplet, sem acesso público.

## Alternativas consideradas

- **SQLite com Litestream (ADR-0003)** — mais barato, mas diverge do ambiente de desenvolvimento escolhido e limita concorrência com vários usuários.
- **MySQL em container no próprio Droplet** — mais barato que o gerenciado, mas exige Droplet maior e backup/restauração feitos por nós.
- **PostgreSQL** — equivalente tecnicamente; MySQL é o padrão do Sail e a preferência do projeto.

## Consequências

- Custo do banco gerenciado (a partir de cerca de US$ 15/mês) e Droplet sem volume de banco.
- Comandos do dia a dia passam por `vendor/bin/sail` (ver [contribuindo.md](../../contribuindo.md)).
- Teste de restauração passa a usar o restore de backup do banco gerenciado.
