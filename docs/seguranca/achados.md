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
| A-07 | 2026-10-28 | composer audit / npm audit | Alta | A03 | Dependências vulneráveis: livewire/livewire (bypass de `#[Authorize]`), league/commonmark, symfony/http-foundation e symfony/mime; no npm, vite-plus 0.3.0 com 5 avisos críticos | Corrigido 2026-10-28 | 253da31 |
| A-08 | 2026-10-29 | security-audit quick run-2, confirmado por teste | Baixa | A07 | Login gastava um bcrypt a mais para e-mail inexistente; o tempo de resposta revelava contas | Corrigido 2026-10-29 | v0.2 run-2 |
| A-09 | 2026-10-29 | security-audit quick run-2, confirmado por teste | Média | A06 | Limite de "esqueci a senha", redefinição e convite usava só o IP do proxy; atrás do cloudflared, um visitante bloquearia todos | Corrigido 2026-10-29 (`TRUSTED_PROXIES`) | v0.2 run-2 |
| A-10 | 2026-10-29 | security-audit quick run-2, crítico de cobertura | Média | A06 | O último administrador ativo podia excluir a própria conta e deixar o sistema sem administrador (RN-17) | Corrigido 2026-10-29 | v0.2 run-2 |

Execução run-1 (perfil `quick`, somente leitura, commit 380a7f9): nenhum achado `confirmed` pela skill; 6 pistas `needs_validation`. A-01 a A-05 foram depois reproduzidas por testes Pest em `tests/Feature/Security/` (falham sem a correção) e corrigidas; a severidade foi atribuída nessa confirmação. A-06 depende da configuração de produção (instalar com `--no-dev`, `APP_ENV=production`, `APP_DEBUG=false`) e será fechada no portão da v0.9. Relatório completo fora do repositório.

Execução run-2 (perfil `quick`, escopo `380a7f9..9579fe4`, v0.1 + v0.2, somente leitura): nenhum `confirmed` pela skill; 3 pistas `needs_validation` (A-06 mantida, A-08 e A-09 novas). A-01 a A-05 revalidadas e fechadas pelo código e pelos testes. A-08, A-09 e A-10 confirmadas por testes em `tests/Feature/Security/AuditRunTwoTest.php`, que falham sem a correção. Adiados pelo perfil `quick` para a próxima auditoria: conclusão do login com 2FA, seeder/`.env`/CI e telas de perfil. Melhorias anotadas (sem fronteira violada): corrida entre dois administradores removendo um ao outro e auditoria fora da transação; limite para tentativas de senha atual; `Referrer-Policy: no-referrer` na tela de convite; e-mail de "esqueci a senha" enviado fora da fila.

Detalhes que exponham como explorar uma falha aberta não entram aqui antes da correção; ficam numa issue privada do GitHub (Security Advisory) e são resumidos depois.
