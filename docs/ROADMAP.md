# Roadmap

Atualizado em 2026-10-28.

O objetivo da **v1.0** é substituir a planilha por completo: lançar horas pelo celular ou pelo computador, fechar o mês de cada cliente, gerar o relatório que acompanha a nota e controlar quem acessa o quê. A emissão da NFS-e vem depois, em etapas.

```mermaid
timeline
    title Versões
    v0.1 Fundação : Qualidade 100%, camadas, cálculo testado, app shell de console
    v0.2 Acesso : Usuários, perfis, permissões granulares, auditoria
    v0.3 Cadastros : Configurações, clientes e taxas, categorias
    v0.4 Lançamentos : Lançamento manual, cronômetro
    v0.5 Faturamento : Fechamento, relatório, painel, importação da planilha
    v1.0 Produção : Deploy na Digital Ocean, backup, PWA
    v1.1 NFS-e assistida : Dados prontos para o Emissor Nacional
    v1.2 NFS-e via API : Emissão automática, IBS/CBS
    v1.3 Fiscal : Fator R e estimativa de impostos
```

## Versões

### v0.1 — Fundação · 🚧 Em desenvolvimento

Base técnica e visual sobre a qual todo o resto é construído.

- [x] Pasta `docs/` com roadmap, regras, funcionalidades, telas e linguagem visual
- [x] Repositório no GitHub com Conventional Commits ([contribuindo.md](contribuindo.md))
- [x] CI: Pint, PHPStan nível máximo, testes com cobertura mínima de 100% ([ADR-0009](arquitetura/adr/0009-qualidade-e-fluxo-de-trabalho.md))
- [x] Testes de arquitetura entre camadas ([ADR-0002](arquitetura/adr/0002-arquitetura-em-camadas.md))
- [x] Value Objects `Money`, `Minutes`, `Points` ([ADR-0004](arquitetura/adr/0004-dinheiro-e-tempo-em-inteiros.md))
- [x] `BillingCalculator` e políticas de arredondamento, com testes das regras RN-01, RN-05, RN-06 e RN-07
- [ ] Locale `pt_BR`, fuso `America/Sao_Paulo`, moeda BRL
- [ ] Tokens visuais e `ui.app-shell` no estilo console ([linguagem-visual.md](design/linguagem-visual.md))
- [ ] Login traduzido e sem cadastro público ([F-01](funcionalidades/F-01-acesso-e-seguranca.md))

### v0.2 — Acesso · 📋 Planejado

- [ ] [F-17 Usuários e permissões](funcionalidades/F-17-usuarios-e-permissoes.md): catálogo, perfis, exceções, "ver como", auditoria
- [ ] [F-01 Acesso e segurança](funcionalidades/F-01-acesso-e-seguranca.md): convite por e-mail, desativação
- [ ] Componentes `ui.guarded` e `ui.guarded-content`

### v0.3 — Cadastros · 📋 Planejado

- [ ] [F-02 Configurações do prestador](funcionalidades/F-02-configuracoes-do-prestador.md)
- [ ] [F-03 Clientes e taxas](funcionalidades/F-03-clientes-e-taxas.md)
- [ ] [F-04 Categorias](funcionalidades/F-04-categorias.md)

### v0.4 — Lançamentos · 📋 Planejado

- [ ] [F-05 Lançamentos de horas](funcionalidades/F-05-lancamentos-de-horas.md)
- [ ] [F-06 Cronômetro](funcionalidades/F-06-cronometro.md)

### v0.5 — Faturamento · 📋 Planejado

- [ ] [F-08 Períodos de faturamento](funcionalidades/F-08-periodos-de-faturamento.md)
- [ ] [F-09 Relatório do período](funcionalidades/F-09-relatorio-do-periodo.md)
- [ ] [F-10 Painel](funcionalidades/F-10-painel.md)
- [ ] [F-07 Importação da planilha](funcionalidades/F-07-importacao-da-planilha.md) (agosto a outubro de 2026)

### v1.0 — Produção · 📋 Planejado

Marco: a planilha deixa de ser usada.

- [ ] Infraestrutura na Digital Ocean ([operacao/deploy.md](operacao/deploy.md))
- [ ] Backup contínuo com Litestream e teste de restauração documentado
- [ ] [F-11 PWA e experiência mobile](funcionalidades/F-11-pwa-e-experiencia-mobile.md)
- [ ] Revisão de segurança (2FA obrigatório para administradores, headers, rate limit no login)

### v1.1 — NFS-e assistida · 📋 Planejado

- [ ] [F-12 Emissão de NFS-e](funcionalidades/F-12-emissao-de-nfse.md) — etapa A: o sistema prepara os dados, a emissão é feita no Emissor Nacional e o XML/PDF é anexado

### v1.2 — NFS-e via API · 📋 Planejado

**Prazo externo:** campos de IBS/CBS passam a valer para o Simples Nacional em **2027-01-01** ([fiscal/nfse.md](fiscal/nfse.md)).

- [ ] [F-12 Emissão de NFS-e](funcionalidades/F-12-emissao-de-nfse.md) — etapa B: emissão pela API do Emissor Nacional, homologação antes de produção
- [ ] Cancelamento e consulta de status

### v1.3 — Fiscal · 📋 Planejado

- [ ] [F-14 Fator R e estimativa de impostos](funcionalidades/F-14-fator-r-e-impostos.md)
- [ ] Meta mensal e previsão de fechamento no painel ([F-10](funcionalidades/F-10-painel.md))

## Backlog (sem versão)

| ID | Ideia |
|---|---|
| [F-13](funcionalidades/F-13-link-publico-do-relatorio.md) | Link público do relatório para o cliente aprovar antes da nota |
| [F-15](funcionalidades/F-15-lembretes.md) | Lembretes: dia sem lançamento, fechamento do mês, nota não paga |
| [F-16](funcionalidades/F-16-integracao-linear.md) | Integração com o Linear para puxar o título das tarefas |
| — | Projetos dentro de um cliente |
| — | Despesas reembolsáveis no período |
| — | Login por passkey |
| — | Permissão por registro (ex.: usuário só vê o cliente X) |
| — | Mutation testing (Infection) |

## Fora do escopo

- Multiempresa: o sistema atende uma única empresa prestadora, com vários usuários.
- Emissão de NF-e de produto.
