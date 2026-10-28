# Documentação — Controle

Sistema web para registrar horas trabalhadas, fechar o período de cada cliente, controlar o acesso de cada usuário e, no futuro, emitir a NFS-e. Substitui a planilha "Controle de Horas".

Esta pasta é a fonte da verdade do produto. O código implementa o que está descrito aqui; quando os dois divergirem, um dos dois está errado e deve ser corrigido no mesmo commit.

## Mapa

| Pasta / arquivo | Conteúdo |
|---|---|
| [ROADMAP.md](ROADMAP.md) | Versões planejadas, o que entra em cada uma e o status |
| [CHANGELOG.md](CHANGELOG.md) | O que mudou em cada versão publicada |
| [produto/visao.md](produto/visao.md) | Problema, objetivo, público e origem (a planilha) |
| [produto/regras-de-negocio.md](produto/regras-de-negocio.md) | Regras numeradas (`RN-xx`) — cálculo, arredondamento, estados |
| [produto/glossario.md](produto/glossario.md) | Termos do domínio |
| [funcionalidades/](funcionalidades/README.md) | Uma página por funcionalidade (`F-xx`) |
| [telas/](telas/README.md) | Uma página por tela (`T-xx`), navegação e checklist de entrega |
| [design/linguagem-visual.md](design/linguagem-visual.md) | Estilo de console, tokens, componentes e o que é proibido |
| [contribuindo.md](contribuindo.md) | Commits, scripts de qualidade e fluxo de trabalho |
| [arquitetura/](arquitetura/visao-geral.md) | Camadas, padrões de projeto, modelo de dados e decisões (ADRs) |
| [seguranca/](seguranca/README.md) | Programa de segurança, controles OWASP (`SEC-xx`), modelo de ameaças, achados |
| [fiscal/](fiscal/nfse.md) | NFS-e, Simples Nacional, Fator R e prazos legais |
| [operacao/deploy.md](operacao/deploy.md) | Infraestrutura na Digital Ocean, backup e deploy |
| [_templates/](_templates/) | Modelos para novas páginas |

## Convenções

**Identificadores.** Cada item tem um ID estável que nunca é reaproveitado:

- `RN-xx` regra de negócio
- `F-xx` funcionalidade
- `T-xx` tela
- `SEC-xx` controle de segurança
- `ADR-xxxx` decisão de arquitetura

Use os IDs para referenciar itens entre documentos, em commits e em testes (ex.: `it('arredonda o período para cima em pontos inteiros — RN-06')`).

**Status.** Funcionalidades e telas usam um dos status abaixo no cabeçalho:

| Status | Significado |
|---|---|
| 💡 Ideia | Registrada, ainda sem decisão |
| 📋 Planejado | Aprovado e com versão definida no roadmap |
| 🚧 Em desenvolvimento | Em andamento |
| ✅ Entregue | Em produção (indicar a versão) |
| 🗄️ Descontinuado | Removido; manter a página com o motivo |

**Quando atualizar.**

- Nova funcionalidade ou tela: crie a página a partir do template antes de começar o código.
- Mudança de regra: altere a `RN` correspondente e cite o ID no commit. Regras não são apagadas; marque como revogadas e aponte a substituta.
- Decisão técnica relevante (biblioteca, padrão, infraestrutura): registre um ADR.
- Ao publicar uma versão: atualize o `CHANGELOG.md`, o status no `ROADMAP.md` e o status das páginas afetadas.

**Estilo.** Português, frases curtas, Markdown simples. Diagramas em Mermaid quando ajudarem. Wireframes de tela em blocos de texto.
