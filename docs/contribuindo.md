# Contribuindo

Como o código e a documentação evoluem neste projeto. Decisão: [ADR-0009](arquitetura/adr/0009-qualidade-e-fluxo-de-trabalho.md).

## Repositório

- Remote: `git@github.com:felipealvesprestes/controle.git`
- Branch principal: `main`. Trabalho maior vai em branch `tipo/descricao-curta` (ex.: `feat/cronometro`) e entra por pull request.

## Idioma

Código em inglês: classes, métodos, variáveis, arquivos, tabelas, colunas, rotas (URL e nome), chaves de permissão, eventos e testes. Português fica só no texto exibido ao usuário (arquivos de tradução `lang/pt_BR`), na documentação em `docs/` e nas mensagens de commit.

## Commits

Padrão [Conventional Commits](https://www.conventionalcommits.org/pt-br/v1.0.0/), mensagem em português, no imperativo, sem ponto final:

```
tipo(escopo opcional): mensagem curta

Corpo opcional explicando o porquê.
```

| Tipo | Quando usar |
|---|---|
| `feat` | Funcionalidade nova para o usuário |
| `fix` | Correção de bug |
| `refactor` | Mudança de código sem alterar comportamento |
| `test` | Só testes |
| `docs` | Só documentação |
| `style` | Formatação, sem mudança de código |
| `perf` | Desempenho |
| `build` | Dependências, build, Docker |
| `ci` | Pipelines |
| `chore` | Manutenção que não se encaixa acima |

Exemplos:

```
feat(billing): arredonda o período para cima em pontos inteiros
fix(time): aceita lançamento que atravessa a meia-noite
docs: adiciona tela de perfis de acesso
```

Mudança que quebra compatibilidade: `feat!:` e um parágrafo `BREAKING CHANGE:` no corpo.

## Antes de cada commit

```bash
composer lint
```

```bash
composer types:check
```

```bash
composer test:coverage
```

| Script | O que faz |
|---|---|
| `composer lint` | Formata com Pint |
| `composer types:check` | PHPStan nível máximo |
| `composer test` | Pint (verificação), PHPStan e testes |
| `composer test:coverage` | Testes com cobertura mínima de 100% (exige pcov) |
| `composer test:coverage:docker` | O mesmo, dentro do container do Sail (pcov já instalado) |

## Segurança

Antes de abrir um PR, peça ao Claude Code: "use a skill security-audit para revisar o diff desta branch contra a main". Achados confirmados seguem o processo de [seguranca/README.md](seguranca/README.md). Nunca versionar relatórios de auditoria, `.env`, certificados ou chaves.

## Documentação

Toda mudança de comportamento atualiza a página correspondente em `docs/` no mesmo commit ou PR. Ver [README.md](README.md#convenções).
