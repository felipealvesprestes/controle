# Changelog

Mudanças visíveis para quem usa o sistema, por versão. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

## [Não publicado]

### Adicionado

- Seeder do administrador inicial (`AdminUserSeeder`), configurado pelo `.env`, e coluna `is_admin` em `users`.

### Alterado

- Banco oficial passa a ser MySQL; desenvolvimento roda no Laravel Sail (ADR-0012).

### Segurança

- Ações de 2FA exigem confirmação de senha recente também nas atualizações do Livewire (A-01).
- Trocar o e-mail exige a senha atual; e-mails são salvos em minúsculas (A-02).
- Trocar ou redefinir a senha encerra as sessões abertas em outros lugares (A-03).
- Link de redefinição de senha sempre usa o endereço oficial do sistema (A-04).
- "Esqueci a senha" responde igual para qualquer e-mail e limita 5 pedidos por minuto (A-05).

### Adicionado

- Documentação inicial do produto: visão, regras de negócio, funcionalidades, telas, arquitetura, fiscal e deploy.
- Cálculo de cobrança com pontos de 15 minutos e três políticas de arredondamento (período, lançamento, exato), coberto por testes com os meses da planilha.
- Etapa de segurança (v0.9) com controles OWASP Top 10:2025, modelo de ameaças, Cloudflare na borda e auditoria com a skill `security-audit` da Cloudflare (ADR-0010, ADR-0011).
- Usuários com permissões granulares (F-17), linguagem visual de console e regras de qualidade (ADR-0007 a ADR-0009).
