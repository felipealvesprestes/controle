# F-01 — Acesso e segurança

| | |
|---|---|
| **Status** | 🚧 Em andamento (2FA obrigatório para administrador na v1.0) |
| **Versão** | v0.1 (ajustes do starter kit), v0.2 (convites), v1.0 (2FA obrigatório para administradores) |
| **Telas** | T-01, T-10, T-12 |
| **Regras** | RN-17, RN-19 |

## Objetivo

Só pessoas cadastradas pelo administrador acessam o sistema. Como ele guardará dados fiscais e, no futuro, o certificado digital, o acesso precisa ser forte. O que cada pessoa pode fazer depois de entrar é definido em [F-17](F-17-usuarios-e-permissoes.md).

## Comportamento

- Login por e-mail e senha (Fortify, já no starter kit).
- **Cadastro público desativado.** O primeiro administrador é criado pelo `AdminUserSeeder`, a partir de `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env` (sem senha, ela é definida pelo "esqueci a senha"; o seeder nunca promove uma conta comum já existente); os demais usuários entram por convite do administrador.
- **Convite:** e-mail com link assinado, válido por 72 h, para definir a senha.
- 2FA por aplicativo autenticador; obrigatório para administradores a partir da v1.0 e opcional (ou exigido pelo administrador) para os demais.
- Sessão longa no celular ("manter conectado").
- Recuperação de senha por e-mail.
- Usuário desativado não faz login e perde as sessões abertas (RN-19).
- Sem exclusão da própria conta: quem sai é desativado pelo administrador, e o histórico continua dizendo quem fez cada mudança (RN-19, RN-20).
- Trocar ou redefinir a senha também anula um desafio de 2FA aberto com a senha antiga (A-03, A-13).

## Critérios de aceite

- [x] A rota de registro não existe.
- [x] Convite expirado ou já usado mostra mensagem clara e não cria sessão (broker `invites`, validade de 72 h).
- [ ] Administrador sem 2FA é levado à configuração antes de usar o sistema (v1.0).
- [x] Login com rate limit (5 tentativas/minuto por e-mail + IP).
- [x] O Perfil não oferece "Excluir conta" e o banco recusa apagar quem tem registro na auditoria.

## Fora de escopo

Login social e passkey (backlog).
