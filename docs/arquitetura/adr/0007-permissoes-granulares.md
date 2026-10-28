# ADR-0007 — Permissões granulares com catálogo em código

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O administrador precisa definir exatamente o que cada usuário acessa, incluindo menus, botões, campos e textos ([F-17](../../funcionalidades/F-17-usuarios-e-permissoes.md)). Uma permissão escondida na interface precisa estar bloqueada também no servidor. O sistema deve continuar fácil de manter e com baixo acoplamento.

## Decisão

**Catálogo em código, atribuição no banco.**

- Cada módulo declara as próprias permissões numa classe `PermissionProvider` (ex.: `BillingPermissions`). O `PermissionCatalog` junta todos os providers registrados. Adicionar um módulo não altera o catálogo (aberto/fechado).
- Uma permissão é um Value Object `Permission(key, type, label, description, screen, dependsOn[])`.
- O banco guarda só a atribuição: `roles`, `role_permission`, `role_user`, `user_permission_overrides` (efeito `allow`/`deny`) e `permission_audits`.
- Resolução no domínio, sem Laravel: `PermissionResolver` calcula as permissões efetivas de um usuário (RN-16) e entrega um `PermissionSet` imutável.
- Porta `Authorizer` no domínio (`allows(Actor, Permission)`), usada pelos casos de uso. Toda escrita checa permissão no caso de uso, não só na tela (RN-18).
- Integração com o Laravel em `Infrastructure`: `Gate::before` consulta o `Authorizer`, então `@can`, `can:` em rotas e `$this->authorize()` no Livewire funcionam com as chaves do catálogo.
- Interface: componente `<x-ui.guarded permission="...">` para menus, botões e campos; `<x-ui.guarded-content permission="...">` para textos, com o substituto `•••`.
- O `PermissionSet` do usuário fica em cache por requisição e é invalidado por evento (`PermissionsChanged`).
- Teste automatizado varre `app/` e `resources/views/` e falha se alguma chave usada não existir no catálogo.

## Alternativas consideradas

- **spatie/laravel-permission** — maduro, mas guarda permissões como linhas no banco (perde descrição, tipo, tela e dependências que vivem no código), não tem conceito de negação explícita e acoplaria o domínio a um pacote. O que precisamos é pequeno o bastante para implementar e testar.
- **Policies do Laravel apenas** — boas para regras por registro, mas não oferecem catálogo configurável pelo administrador.
- **Permissões só na interface** — inseguro: ações ficariam acessíveis por requisição direta.

## Consequências

- Uma fonte de verdade para o que existe (código) e outra para quem tem (banco), sem migração para criar permissão nova.
- Toda tela nova precisa declarar suas permissões no provider do módulo; o teste de catálogo lembra disso.
- Administrador ignora a resolução (tem tudo), mas a regra do último administrador ativo (RN-17) fica no domínio.
