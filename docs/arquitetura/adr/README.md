# Decisões de arquitetura (ADR)

Cada decisão técnica relevante vira um arquivo numerado, criado a partir de [_templates/adr.md](../../_templates/adr.md). ADRs não são editados depois de aceitos: se a decisão mudar, crie um novo e marque o antigo como "Substituída por".

| ADR | Decisão | Status |
|---|---|---|
| [0001](0001-stack.md) | Stack: Laravel 13, Livewire 4 + Flux, Tailwind 4 | Aceita |
| [0002](0002-arquitetura-em-camadas.md) | Domínio isolado em camadas | Aceita |
| [0003](0003-sqlite-com-litestream.md) | SQLite com backup contínuo via Litestream | Aceita |
| [0004](0004-dinheiro-e-tempo-em-inteiros.md) | Dinheiro em centavos e tempo em minutos inteiros | Aceita |
| [0005](0005-snapshot-no-fechamento.md) | Snapshot dos totais no fechamento do período | Aceita |
| [0006](0006-emissao-nfse-por-adaptadores.md) | Emissão de NFS-e por porta e adaptadores, mirando o Emissor Nacional | Aceita |
| [0007](0007-permissoes-granulares.md) | Permissões granulares com catálogo em código | Aceita |
| [0008](0008-linguagem-visual-de-console.md) | Linguagem visual de console | Aceita |
| [0009](0009-qualidade-e-fluxo-de-trabalho.md) | Cobertura de 100%, PHPStan máximo, Pint e Conventional Commits | Aceita |
