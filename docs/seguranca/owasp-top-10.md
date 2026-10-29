# Controles por categoria do OWASP Top 10:2025

Cada controle tem um ID `SEC-xx` estável, a versão em que deve estar pronto e como é verificado. Status: 📋 planejado · 🚧 em andamento · ✅ implementado e verificado.

## A01 — Broken Access Control

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-01 | Negado por padrão: sem permissão explícita, nada é acessível (RN-15) | Teste: usuário sem perfil recebe 403 em todas as rotas autenticadas | v0.2 | ✅ |
| SEC-02 | Toda ação que altera dados checa permissão no caso de uso via `Authorizer`, não só na tela (RN-18) | Teste de arquitetura: casos de uso de escrita recebem `Actor`; teste de 403 por chamada direta ao Livewire | v0.2 | ✅ |
| SEC-03 | Escopo de dados: usuário sem `*.scope.all` só lê e altera os próprios registros (sem IDOR) | Teste: trocar o ID na requisição responde "não existe mais" (`TimeEntryScopeTest`) | v0.4 | ✅ |
| SEC-04 | IDs e campos sensíveis de componentes Livewire com `#[Locked]`; nada de confiar em propriedade pública vinda do navegador | Teste: alterar propriedade travada falha | v0.2 | ✅ |
| SEC-05 | Atribuição em massa bloqueada: `Model::shouldBeStrict()`, `$fillable` explícito, dados entram só por DTO do caso de uso | PHPStan + teste | v0.1 | 📋 |
| SEC-06 | Arquivos privados (XML, PDF, certificado) fora de `public/`, servidos só por rota autorizada | Teste: acesso sem permissão retorna 403 | v1.1 | 📋 |
| SEC-07 | Links assinados e com validade para convites e relatório público; revogáveis | Teste: link alterado ou expirado é recusado | v0.2 / backlog | 🚧 (convite ✅) |

## A02 — Security Misconfiguration

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-08 | Produção com `APP_DEBUG=false`, `APP_ENV=production`, sem rotas de debug, sem `phpinfo` | Teste de configuração no deploy; ZAP | v0.9 | 📋 |
| SEC-09 | Cabeçalhos: CSP restritiva (com nonce, sem `unsafe-inline` em script), HSTS com preload, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `frame-ancestors 'none'` | Teste de middleware + Mozilla Observatory A+ | v0.9 | 📋 |
| SEC-10 | Cookies `Secure`, `HttpOnly`, `SameSite=Lax`; sessão criptografada; prefixo `__Host-` | Teste de configuração | v0.9 | 📋 |
| SEC-11 | Servidor sem portas HTTP abertas: tráfego só pelo Cloudflare Tunnel; SSH restrito | Varredura externa de portas | v0.9 | 📋 |
| SEC-12 | TLS 1.2+ ponta a ponta, Cloudflare em modo Full (strict) | SSL Labs A+ | v0.9 | 📋 |
| SEC-13 | Sem cabeçalhos que revelam versão (`X-Powered-By`, `Server`) | ZAP | v0.9 | 📋 |

## A03 — Software Supply Chain Failures

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-14 | `composer audit` e `npm audit` no CI, falhando em vulnerabilidade alta | CI | v0.1 | ✅ |
| SEC-15 | Dependabot para Composer, npm e GitHub Actions; actions fixadas por SHA | Configuração do repositório | v0.1 | ✅ |
| SEC-16 | Dependência nova só com aprovação e ADR quando relevante; lockfiles versionados | Revisão de PR | v0.1 | ✅ |
| SEC-17 | Imagem Docker de produção com base fixada por digest e sem ferramentas de build | Revisão do Dockerfile | v0.9 | 📋 |

## A04 — Cryptographic Failures

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-18 | Certificado A1 criptografado em repouso com chave própria; senha só em variável de ambiente | Teste: arquivo salvo não é um PKCS#12 legível | v1.2 | 📋 |
| SEC-19 | Senhas com hash forte (bcrypt/argon2id), segredos de 2FA criptografados (Fortify) | Teste | v0.1 | ✅ (starter kit) |
| SEC-20 | Banco só na VPC; backups do MySQL gerenciado e arquivos criptografados no Spaces; bucket privado | Revisão de configuração | v0.9 | 📋 |
| SEC-21 | Nada sensível em URL, log ou mensagem de erro (CPF/CNPJ, valores, tokens) | Teste de logs + revisão | v0.2 | 📋 |
| SEC-22 | Plano de rotação da `APP_KEY` (`APP_PREVIOUS_KEYS`) documentado | Documento | v0.9 | 📋 |

## A05 — Injection

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-23 | SQL só por Eloquent/Query Builder com bindings; `DB::raw`/`whereRaw` proibidos fora de lista revisada | Teste de arquitetura | v0.1 | ✅ |
| SEC-24 | Saída sempre escapada (`{{ }}`); `{!! !!}` proibido salvo exceção revisada | Teste que varre as views | v0.1 | ✅ |
| SEC-25 | XML (NFS-e) lido com entidades externas desativadas (XXE) e tamanho máximo | Teste com payload XXE | v1.1 | 📋 |
| SEC-26 | Exportação CSV neutraliza fórmulas (`=`, `+`, `-`, `@`, tab, CR no início da célula) | Teste com payload | v0.5 | 📋 |
| SEC-27 | `.xlsx` importado: limite de tamanho, de linhas e de descompressão (zip bomb), sem fórmulas executadas | Teste com arquivo malicioso | v0.5 | 📋 |

## A06 — Insecure Design

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-28 | Toda funcionalidade tem seção "Segurança" com ameaças e casos de abuso antes do código | Revisão de PR | v0.1 | 📋 |
| SEC-29 | Regras de negócio críticas como invariantes do domínio (período fechado imutável, último administrador) | Testes de domínio | v0.2 | ✅ |
| SEC-30 | Limites de uso em ações caras (emitir nota, exportar, importar, convidar) | Teste de rate limit | v0.5 | 📋 |

## A07 — Authentication Failures

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-31 | Rate limit no login, no 2FA e no esqueci a senha (por IP) e na borda (Cloudflare) | Teste + regra na Cloudflare | v0.1 | 🚧 (aplicação ✅, borda 📋) |
| SEC-32 | Turnstile no login e na recuperação de senha | Teste com token inválido | v0.9 | 📋 |
| SEC-33 | 2FA obrigatório para administradores | Teste | v0.9 | 📋 |
| SEC-34 | Reautenticação (senha) para ações sensíveis: 2FA, troca de e-mail, mudar permissões, enviar certificado, emitir/cancelar nota | Teste | v0.2 | 🚧 (2FA, e-mail e permissões ✅) |
| SEC-35 | Sessão regenerada no login, encerrada quando a senha muda, expira por inatividade (administrador: 30 min), encerrada ao desativar usuário (RN-19) | Teste | v0.2 | 🚧 (login, troca de senha e desativação ✅; inatividade pendente) |
| SEC-36 | Mensagens que não revelam se o e-mail existe (login, recuperação, convite) | Teste | v0.1 | 🚧 (login e recuperação ✅, convite 📋) |
| SEC-37 | Cloudflare Access (e-mail + código) na frente de `/admin` como segunda barreira | Revisão de configuração | v0.9 | 📋 |

## A08 — Software or Data Integrity Failures

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-38 | Deploy só pelo CI, a partir da `main` protegida (PR com CI verde obrigatório) | Configuração do GitHub | v0.9 | 📋 |
| SEC-39 | Nunca `unserialize` de dado externo; payloads da fila assinados (padrão do Laravel) | Teste de arquitetura | v0.1 | ✅ |
| SEC-40 | XML de NFS-e anexado tem valor e CNPJ conferidos com o período antes de aceitar | Teste | v1.1 | 📋 |

## A09 — Security Logging and Alerting Failures

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-41 | Registro de auditoria imutável para usuários, permissões e períodos (RN-20) | Teste | v0.2 | ✅ |
| SEC-42 | Log de eventos de autenticação: sucesso, falha, bloqueio, 2FA, troca de senha | Teste | v0.2 | 📋 |
| SEC-43 | Alerta por e-mail ao administrador: muitas falhas de login, mudança de permissões, novo administrador, certificado enviado | Teste | v0.9 | 📋 |
| SEC-44 | Logs sem segredos nem dados pessoais desnecessários; retenção de 90 dias | Revisão | v0.9 | 📋 |

## A10 — Mishandling of Exceptional Conditions

| ID | Controle | Verificação | Versão | Status |
|---|---|---|---|---|
| SEC-45 | Erros mostram página genérica com código de referência; detalhe só no log | Teste | v0.1 | 📋 |
| SEC-46 | Falha na checagem de permissão nega o acesso (fail closed) | Teste | v0.2 | ✅ |
| SEC-47 | Casos de uso de escrita em transação; nada fica pela metade | Teste | v0.4 | 📋 |
| SEC-48 | Chamadas externas (Emissor Nacional, e-mail) com timeout, nova tentativa limitada e idempotência | Teste | v1.2 | 📋 |
