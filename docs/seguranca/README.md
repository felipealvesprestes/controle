# Segurança

O sistema guarda dados fiscais, dados de clientes, o certificado digital da empresa e vai emitir notas fiscais. Ele só entra na web depois de passar pelo **portão de segurança** desta página.

| Documento | Conteúdo |
|---|---|
| [owasp-top-10.md](owasp-top-10.md) | Controles do sistema (`SEC-xx`) mapeados para o OWASP Top 10:2025 |
| [modelo-de-ameacas.md](modelo-de-ameacas.md) | O que proteger, de quem, e as ameaças de cada funcionalidade |
| [achados.md](achados.md) | Registro de vulnerabilidades encontradas, severidade e correção |
| [ADR-0010](../arquitetura/adr/0010-cloudflare-na-borda.md) | Cloudflare na frente da aplicação |

## Referências

- **OWASP Top 10:2025** — categorias de risco usadas para organizar os controles.
- **OWASP ASVS 5.0, nível 2** — lista de verificação detalhada usada na auditoria antes de produção. Nível 2 é o recomendado para aplicações com dados sensíveis.
- **OWASP Cheat Sheet Series** — referência de implementação (Laravel, CSP, XXE, CSV injection, sessões).

## Duas frentes

| Frente | O que cobre | Ferramentas |
|---|---|---|
| **Código** | Controle de acesso, injeção, autenticação, tratamento de arquivos e XML, segredos, dependências | Skill **`security-audit` da Cloudflare**, testes de segurança no Pest, PHPStan, `composer audit`, `npm audit`, gitleaks |
| **Borda e infraestrutura** | DDoS, bots, força bruta, WAF, TLS, cabeçalhos, exposição do servidor | Cloudflare (proxy, WAF, Tunnel, Turnstile, Access), OWASP ZAP, SSL Labs, Mozilla Observatory |

As duas frentes são obrigatórias: a skill audita o código, mas declara como `needs_validation` tudo o que depende de configuração fora do repositório (Cloudflare, servidor, cabeçalhos do proxy). Isso é verificado na frente de borda.

## Skill `security-audit` da Cloudflare

Instalada em `.claude/skills/security-audit/` por cópia do repositório oficial [cloudflare/security-audit-skill](https://github.com/cloudflare/security-audit-skill) (MIT). Origem e commit em `SOURCE.txt`. Para atualizar, copiar de novo a pasta `skills/security-audit` de um commit revisado e registrar o novo commit.

**Como funciona:** auditoria em seis fases (reconhecimento, caça por cobertura, validação de cada candidato por um verificador independente, saída estruturada em `findings.json`, verificação final e relatório). Cada achado sai como `confirmed` (com severidade), `needs_validation` (sem severidade, falta um fato externo) ou `rejected`. Execuções repetidas aproveitam as anteriores e focam no que mudou.

| Modo | Quando usar | Como pedir ao Claude Code |
|---|---|---|
| Orientação | Revisão focada de um PR ou de uma funcionalidade | "use a skill security-audit para revisar o diff desta branch contra a main" |
| Auditoria completa, perfil `quick` ou escopo do diff | Ao fechar cada versão | "faça uma auditoria de segurança com a skill security-audit, perfil quick, escopo: diff entre a tag da versão anterior e a main" |
| Auditoria completa, perfil `deep` | Portão da v0.9 e antes da v1.2 (NFS-e via API) | "faça uma auditoria de segurança completa com a skill security-audit, perfil deep" |

**Regras de uso neste projeto:**

- Relatórios ficam **fora do repositório** (padrão da skill: `~/security-audit-skill/controle/run-N`). Eles descrevem como explorar falhas e não devem ser versionados.
- Achados `confirmed` são registrados em [achados.md](achados.md) (resumo) e viram issue privada (GitHub Security Advisory) até a correção. Cada correção vem com teste de regressão.
- Achados `needs_validation` viram itens da frente de borda ou do checklist ASVS.
- A skill só executa código do projeto dentro de sandbox sem rede e com limites. Sem esse sandbox, ela trabalha só por leitura do código e deixa a confirmação dinâmica como `needs_validation`; essa confirmação é feita depois pelos testes do Pest e pelo ZAP na homologação.
- Os validadores da skill precisam de Node.js, já usado no projeto.

## Processo

### Em todo pull request

- [ ] CI: `composer audit`, `npm audit`, gitleaks (segredos), PHPStan, testes com cobertura de 100%.
- [ ] Testes de segurança da funcionalidade (acesso negado, entrada maliciosa) passam.
- [ ] Revisão do diff com a skill `security-audit` (modo orientação).
- [ ] Seção **Segurança** da página da funcionalidade preenchida (ameaças e controles `SEC-xx`).

### Em toda versão

- [ ] Atualizar o [modelo de ameaças](modelo-de-ameacas.md) com as funcionalidades novas.
- [ ] Auditoria `quick` da skill `security-audit` no diff da versão.
- [ ] Conferir o status dos controles afetados em [owasp-top-10.md](owasp-top-10.md).
- [ ] Nenhum achado crítico ou alto aberto em [achados.md](achados.md).

### Portão antes de produção (v0.9)

Ver a etapa v0.9 no [roadmap](../ROADMAP.md). O sistema só vai para a web quando:

- [ ] Todos os controles `SEC-xx` marcados para v0.9 ou antes estão ✅.
- [ ] Auditoria completa com a skill `security-audit`, perfil `deep`, sem achados `confirmed` críticos ou altos abertos, e todos os `needs_validation` resolvidos ou aceitos por escrito.
- [ ] Checklist ASVS nível 2 revisado.
- [ ] OWASP ZAP (varredura completa autenticada) contra a homologação sem alertas altos.
- [ ] SSL Labs nota A+ e Mozilla Observatory nota A+ (ou A com justificativa).
- [ ] Configuração da Cloudflare revisada (WAF, regras de rate limit, Tunnel, Access, TLS).
- [ ] Restauração de backup testada e plano de resposta a incidentes escrito.

### Depois de produção

- Dependabot e `composer audit` diários no CI.
- OWASP ZAP baseline agendado semanalmente contra produção.
- Revisão mensal dos eventos de segurança da Cloudflare e do registro de auditoria.
- Auditoria `deep` da skill `security-audit` a cada versão maior e antes da emissão de NFS-e via API (v1.2).

## Resposta a incidentes

Resumo; o plano completo é entregue na v0.9.

1. **Conter:** desativar usuários afetados, revogar sessões, ativar o modo "Under Attack" da Cloudflare, colocar a aplicação em manutenção se preciso.
2. **Preservar:** exportar logs da aplicação, da Cloudflare e o registro de auditoria antes de qualquer correção.
3. **Corrigir:** trocar segredos expostos (`APP_KEY` com `APP_PREVIOUS_KEYS`, senha do certificado, chaves do Spaces), corrigir a falha, registrar em [achados.md](achados.md).
4. **Comunicar:** se houver dados pessoais envolvidos, avaliar a comunicação à ANPD e aos titulares (LGPD, art. 48) com apoio jurídico.
