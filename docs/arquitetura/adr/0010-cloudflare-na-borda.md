# ADR-0010 — Cloudflare na borda

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O sistema vai para a web com dados fiscais, certificado digital e permissões de administrador. Um Droplet exposto diretamente fica sujeito a varredura de portas, força bruta, bots e DDoS, e toda a defesa ficaria dentro da aplicação.

## Decisão

Colocar a Cloudflare na frente de tudo:

- **DNS com proxy** no domínio da aplicação; TLS **Full (strict)**, TLS 1.2 mínimo, HSTS.
- **Cloudflare Tunnel** (`cloudflared` como container no Compose): o Droplet não abre portas 80/443 para a internet. O firewall da DO aceita apenas SSH de IPs autorizados.
- **WAF:** regras gerenciadas disponíveis no plano contratado. O **OWASP Core Ruleset** gerenciado da Cloudflare exige plano pago; avaliar o plano Pro na v0.9 e registrar a decisão aqui.
- **Rate limiting** na borda para `/login`, `/two-factor-challenge`, `/forgot-password` e rotas de exportação, somando ao rate limit da aplicação (SEC-31).
- **Turnstile** no login e na recuperação de senha (SEC-32).
- **Cloudflare Access** na frente de `/admin/*`, exigindo e-mail autorizado + código, como segunda barreira para as telas de usuários e perfis (SEC-37).
- **Bot Fight Mode** e modo **Under Attack** disponível para incidentes.
- A aplicação confia no IP do cliente apenas pelo cabeçalho `CF-Connecting-IP` vindo do Tunnel (`TrustProxies` restrito).

## Alternativas consideradas

- **Droplet exposto com Caddy e Let's Encrypt** (plano original) — mais simples, mas expõe o servidor e deixa a proteção contra bots e DDoS só para a aplicação.
- **Firewall da DO aceitando só IPs da Cloudflare** — protege menos que o Tunnel (a lista de IPs muda e o servidor continua com portas abertas).
- **WAF próprio (ModSecurity)** — mais operação para manter.

## Consequências

- O HTTPS público passa a ser responsabilidade da Cloudflare; o Caddy do FrankenPHP atende só o Tunnel, internamente.
- Ponto único de dependência na Cloudflare; o plano de incidente inclui como desviar o DNS em emergência.
- A configuração da Cloudflare fica fora do repositório. Ela é documentada em [operacao/deploy.md](../../operacao/deploy.md) e revisada no portão de segurança. A skill `security-audit` marca esses controles como `needs_validation`, e a revisão acontece na frente de borda.
