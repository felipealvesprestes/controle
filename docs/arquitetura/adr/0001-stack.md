# ADR-0001 — Stack

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O sistema tem um único usuário, precisa funcionar bem no celular, ser barato de hospedar na Digital Ocean e usar a menor stack possível. O projeto já foi iniciado com o **Laravel Livewire Starter Kit** (Laravel 13, Livewire 4, Flux, Fortify, Pest, Larastan, Pint, Tailwind 4).

## Decisão

Manter o starter kit como base:

- **Laravel 13** em PHP 8.5 — monólito, sem API separada.
- **Livewire 4 + Flux (componentes gratuitos)** para as telas. Livewire já traz Alpine.js para interações locais (cronômetro, modais).
- **Tailwind CSS 4** via Vite.
- **Fortify** para login e 2FA.
- **Pest**, **Larastan** e **Pint** no CI.
- Fila e cache com driver `database` (sem Redis).
- PWA (manifest + service worker) para instalar no celular.

## Alternativas consideradas

- **Blade + Alpine sem Livewire** — menos dependências, mas mais JavaScript escrito à mão para formulários reativos; o starter kit já resolve.
- **SPA (React/Vue) + API** — duas aplicações para manter, sem ganho para um usuário só.
- **Flux Pro** — não é necessário; se algum componente fizer falta, avaliar em novo ADR.

## Consequências

- Uma linguagem (PHP) para quase tudo; deploy de um único container.
- Telas dependem de conexão (Livewire faz requisições ao servidor). Uso offline fica limitado ao cronômetro, que guarda o início localmente ([F-06](../../funcionalidades/F-06-cronometro.md)).
- Novas dependências exigem aprovação e, se relevantes, um ADR.
