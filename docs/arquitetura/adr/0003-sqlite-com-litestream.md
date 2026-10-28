# ADR-0003 — SQLite com backup contínuo via Litestream

| | |
|---|---|
| **Status** | Substituída por [ADR-0012](0012-mysql-e-sail.md) |
| **Data** | 2026-10-28 |

## Contexto

Um usuário, poucas centenas de lançamentos por mês. O objetivo é o menor custo e a menor operação possíveis na Digital Ocean, sem perder dados fiscais.

## Decisão

- Banco **SQLite** em modo WAL, num volume persistente do Droplet.
- **Litestream** replica o banco continuamente para um bucket do **DO Spaces**, com retenção de 30 dias.
- Teste de restauração documentado em [operacao/deploy.md](../../operacao/deploy.md) e executado a cada versão maior.

## Alternativas consideradas

- **Postgres gerenciado da DO** — mais robusto, mas custa a partir de cerca de US$ 15/mês e é mais uma peça para manter.
- **MySQL em container (compose atual do Sail)** — operação de banco sem benefício para um usuário. O `compose.yaml` do Sail fica só para desenvolvimento, se for usado.

## Consequências

- Sem servidor de banco. Backup barato e com recuperação em qualquer ponto no tempo.
- Uma única instância da aplicação (SQLite não é compartilhado entre servidores). Aceitável no escopo atual.
- Os repositórios isolam o banco: migrar para Postgres no futuro exige só configuração e migração de dados.
