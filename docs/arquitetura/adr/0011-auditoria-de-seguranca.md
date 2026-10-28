# ADR-0011 — Auditoria de segurança com OWASP e a skill da Cloudflare

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O sistema precisa estar o mais seguro possível antes de ir para a web. Checklists sozinhos geram falsos positivos e deixam passar falhas de lógica (controle de acesso, escopo de dados), que são o maior risco num sistema com permissões granulares.

## Decisão

- **Referência:** OWASP Top 10:2025 para organizar os controles (`SEC-xx`) e OWASP ASVS 5.0 nível 2 como checklist de auditoria. Ver [seguranca/](../../seguranca/README.md).
- **Auditoria do código:** skill [`security-audit` da Cloudflare](https://github.com/cloudflare/security-audit-skill), instalada no projeto por cópia de um commit revisado (`.claude/skills/security-audit/SOURCE.txt`), sem `npx`.
  - Modo orientação em cada PR.
  - Perfil `quick` (escopo do diff) em cada versão.
  - Perfil `deep` no portão da v0.9 e antes da v1.2.
- **Borda:** revisão da configuração da Cloudflare ([ADR-0010](0010-cloudflare-na-borda.md)), OWASP ZAP autenticado na homologação, SSL Labs e Mozilla Observatory.
- **CI:** `composer audit`, `npm audit` e gitleaks bloqueiam o merge.
- **Portão:** nenhum achado `confirmed` crítico ou alto aberto, e nenhum `needs_validation` sem decisão registrada, para publicar.
- Relatórios completos ficam fora do repositório; o resumo vai para [achados.md](../../seguranca/achados.md).

## Alternativas consideradas

- **Só a skill `security-review` embutida no Claude Code** — boa para revisar diffs, mas sem fases de validação independente nem registro de cobertura entre execuções.
- **Plugins de auditoria de terceiros do diretório** — preferência por fontes oficiais; a skill da Cloudflare é mantida pela própria empresa e tem licença MIT.
- **Pentest externo pago** — recomendado quando houver clientes acessando por link público (F-13) ou volume relevante de notas; reavaliar após a v1.2.

## Consequências

- Cada versão tem um custo de auditoria em tempo de agente; o perfil `quick` limita esse custo nas versões intermediárias.
- Atualizar a skill exige revisar o diff do repositório da Cloudflare antes de copiar.
