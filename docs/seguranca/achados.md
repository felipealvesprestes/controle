# Achados de segurança

Registro de toda vulnerabilidade encontrada (auditoria, ZAP, revisão de PR, relato externo). Nunca apagar uma linha: achados corrigidos ficam com a data e o commit da correção.

**Severidade:** Crítica · Alta · Média · Baixa · Informativa (CVSS 4.0 como referência).
**Regra do portão:** nenhum achado Crítico ou Alto aberto para publicar uma versão.

| ID | Data | Origem | Severidade | Categoria OWASP | Descrição | Status | Correção |
|---|---|---|---|---|---|---|---|
| A-01 | 2026-10-28 | security-audit quick run-1, confirmado por teste | Alta | A07 | Ações de 2FA no Livewire sem reconfirmação de senha | Corrigido 2026-10-28 | 4b5f4ee |
| A-02 | 2026-10-28 | security-audit quick run-1, confirmado por teste | Alta | A07 | Troca de e-mail sem senha nem verificação redireciona a recuperação de conta | Corrigido 2026-10-28 | e5eec66 |
| A-03 | 2026-10-28 | security-audit quick run-1, confirmado por teste | Média | A07 | Sessões ativas sobrevivem à troca/reset de senha | Corrigido 2026-10-28 | 619faad |
| A-04 | 2026-10-28 | security-audit quick run-1, confirmado por teste | Média | A02 | Link de reset de senha usa Host/esquema da requisição | Corrigido 2026-10-28 | bc7a2b7 |
| A-05 | 2026-10-28 | security-audit quick run-1, confirmado por teste | Baixa | A07 | `/forgot-password` revela se o e-mail existe e não tem limite | Corrigido 2026-10-28 | bc7a2b7 |
| A-06 | 2026-10-28 | security-audit quick run-1 | Pendente de validação | A02 | Endpoint de logs do Boost (dev) sem auth/CSRF alimenta o MCP do agente | Aberto | — |

Execução run-1 (perfil `quick`, somente leitura, commit 380a7f9): nenhum achado `confirmed` pela skill; 6 pistas `needs_validation`. A-01 a A-05 foram depois reproduzidas por testes Pest em `tests/Feature/Security/` (falham sem a correção) e corrigidas; a severidade foi atribuída nessa confirmação. A-06 depende da configuração de produção (instalar com `--no-dev`, `APP_ENV=production`, `APP_DEBUG=false`) e será fechada no portão da v0.9. Relatório completo fora do repositório.

Detalhes que exponham como explorar uma falha aberta não entram aqui antes da correção; ficam numa issue privada do GitHub (Security Advisory) e são resumidos depois.
