# Changelog

Mudanças visíveis para quem usa o sistema, por versão. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

As versões abaixo de 1.0 são marcos internos do [roadmap](ROADMAP.md); nenhuma está em produção.

## [Não publicado]

### Adicionado

- Padrões de cobrança (F-02): taxa horária padrão e política de arredondamento, com exemplo ao vivo do valor de 1h07.
- Dados da empresa (F-02): razão social, nome fantasia, CNPJ (numérico ou alfanumérico), inscrição municipal (obrigatória; sem inscrição estadual) e endereço.
- Grupo Configurações no menu, com permissões próprias para ver a taxa e para alterar cada tela.
- O passo "Dados da empresa" do Painel passa a valer e leva à tela.
- Endereço preenchido pelo CEP (ViaCEP): logradouro, bairro, cidade e UF, com aviso quando o CEP não existe ou a busca não responde (ADR-0013).
- Clientes (F-03): lista com abas Ativos e Inativos, cadastro rápido pelo painel lateral e página do cliente com nome curto, cor, política de arredondamento, CPF ou CNPJ, razão social, e-mail, retém ISS e endereço pelo CEP.
- Taxas por cliente com vigência (RN-04): a vigente em destaque, reajustes programados por data e o padrão do prestador quando o cliente não tem taxa própria.
- Aviso de dados fiscais incompletos, dizendo o que falta para a nota.
- Inativar, reativar e excluir cliente, com histórico de todas as alterações.
- Grupo Cadastros no menu e permissões próprias para ver CPF/CNPJ e endereço, ver e alterar taxas, alterar, inativar e excluir clientes.
- O passo "Primeiro cliente e taxa" do Painel passa a valer e leva à lista.
- Categorias (F-04): Desenvolvimento, Reunião, Bugfix, Planejamento e Outros criadas pelo seeder; criar, renomear, mudar a cor, marcar como não faturável (RN-12), reordenar arrastando ou pelos botões, inativar, reativar e excluir, com histórico.

### Alterado

- Rótulos ficam 12 px acima dos campos em todos os formulários (antes, 8 px).

### Segurança

- Taxa horária limitada a R$ 100.000,00; valores maiores davam erro 500 em vez de erro de validação (A-11).
- Dois cadastros de cliente com o mesmo nome ao mesmo tempo mostram erro no campo em vez de erro 500 (A-12).
- Excluir um cliente deixa só o nome no histórico, sem CPF, e-mail ou endereço.

## [0.2.0] — 2026-10-29 · Acesso

### Adicionado

- Usuários e perfis de acesso (F-17): convite por e-mail, desativação, administradores, perfis com permissões granulares por menu, página, ação, campo e conteúdo, exceções por usuário e histórico de auditoria.
- Conteúdo sem permissão aparece mascarado (`•••`); menus e páginas sem permissão não aparecem e respondem 403.
- Navegação entre telas troca só o conteúdo: barra superior e menu ficam fixos e o CSS não é baixado de novo.

### Segurança

- Gerenciar usuários e perfis exige senha recente; desativar um usuário encerra as sessões dele (RN-19).
- Login recusa contas desativadas com a mesma mensagem de credencial inválida.
- Login leva o mesmo tempo para e-mail existente ou não (A-08).
- Limites por cliente usam o IP real atrás do proxy configurado em `TRUSTED_PROXIES` (A-09).
- O último administrador ativo não pode excluir a própria conta (A-10).

## [0.1.0] — 2026-10-28 · Fundação

### Adicionado

- Documentação inicial do produto: visão, regras de negócio, funcionalidades, telas, arquitetura, fiscal e deploy.
- Cálculo de cobrança com pontos de 15 minutos e três políticas de arredondamento (período, lançamento, exato), coberto por testes com os meses da planilha.
- Etapa de segurança (v0.9) com controles OWASP Top 10:2025, modelo de ameaças, Cloudflare na borda e auditoria com a skill `security-audit` da Cloudflare (ADR-0010, ADR-0011).
- Decisões de permissões granulares, linguagem visual de console e regras de qualidade (ADR-0007 a ADR-0009).
- Interface no estilo console (referência GCP): barra superior, menu lateral agrupado, faixa de cabeçalho com abas, modo claro e escuro, IBM Plex local.
- Painel com a configuração inicial real e a regra de cobrança calculada com outubro da planilha.
- Interface em português (pt_BR) e fuso de exibição America/Sao_Paulo.
- CI barra segredos (gitleaks) e dependências vulneráveis; testes barram SQL cru, saída sem escape e `unserialize`.
- Seeder do administrador inicial (`AdminUserSeeder`), configurado pelo `.env`, e coluna `is_admin` em `users`.

### Alterado

- Banco oficial passa a ser MySQL; desenvolvimento roda no Laravel Sail (ADR-0012).

### Segurança

- Dependências vulneráveis atualizadas e vite-plus substituído pelo vite (A-07).
- Ações de 2FA exigem confirmação de senha recente também nas atualizações do Livewire (A-01).
- Trocar o e-mail exige a senha atual; e-mails são salvos em minúsculas (A-02).
- Trocar ou redefinir a senha encerra as sessões abertas em outros lugares (A-03).
- Link de redefinição de senha sempre usa o endereço oficial do sistema (A-04).
- "Esqueci a senha" responde igual para qualquer e-mail e limita 5 pedidos por minuto (A-05).
