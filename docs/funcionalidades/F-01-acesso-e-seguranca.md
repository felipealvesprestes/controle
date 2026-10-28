# F-01 — Acesso e segurança

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.1 (ajustes do starter kit), v0.2 (convites), v1.0 (2FA obrigatório para administradores) |
| **Telas** | T-01, T-10, T-12 |
| **Regras** | RN-17, RN-19 |

## Objetivo

Só pessoas cadastradas pelo administrador acessam o sistema. Como ele guardará dados fiscais e, no futuro, o certificado digital, o acesso precisa ser forte. O que cada pessoa pode fazer depois de entrar é definido em [F-17](F-17-usuarios-e-permissoes.md).

## Comportamento

- Login por e-mail e senha (Fortify, já no starter kit).
- **Cadastro público desativado.** O primeiro administrador é criado por comando (`php artisan app:create-admin`); os demais usuários entram por convite do administrador.
- **Convite:** e-mail com link assinado, válido por 72 h, para definir a senha.
- 2FA por aplicativo autenticador; obrigatório para administradores a partir da v1.0 e opcional (ou exigido pelo administrador) para os demais.
- Sessão longa no celular ("manter conectado").
- Recuperação de senha por e-mail.
- Usuário desativado não faz login e perde as sessões abertas (RN-19).

## Critérios de aceite

- [ ] A rota de registro não existe.
- [ ] Convite expirado ou já usado mostra mensagem clara e não cria sessão.
- [ ] Administrador sem 2FA é levado à configuração antes de usar o sistema (v1.0).
- [ ] Login com rate limit (5 tentativas/minuto por e-mail + IP).

## Fora de escopo

Login social e passkey (backlog).
