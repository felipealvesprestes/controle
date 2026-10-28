# F-12 — Emissão de NFS-e

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v1.1 (etapa A), v1.2 (etapa B) |
| **Telas** | T-06, T-10 |
| **Regras** | RN-09, RN-11 |

## Objetivo

Emitir a nota de um período fechado sem redigitar dados, com o valor exato do snapshot.

Contexto legal e prazos: [fiscal/nfse.md](../fiscal/nfse.md). Arquitetura: [ADR-0006](../arquitetura/adr/0006-emissao-nfse-por-adaptadores.md).

## Etapa A — assistida (v1.1)

- No período fechado, botão **Preparar nota** mostra cada campo do Emissor Nacional com botão de copiar: CNPJ do tomador, descrição gerada pelo template, valor, códigos de serviço, retenção de ISS.
- Link direto para o site do Emissor Nacional.
- Depois de emitir, anexar XML (e opcionalmente PDF). O sistema lê do XML número, chave de acesso, data e valor, confere com o snapshot e move o período para **Faturado**.

### Critérios de aceite

- [ ] Valor do XML diferente do snapshot gera alerta e exige confirmação.
- [ ] XML e PDF ficam em disco privado, acessíveis só pelo usuário logado.

## Etapa B — API (v1.2)

- Botão **Emitir nota**: monta a DPS, assina com o certificado A1 e envia em job na fila.
- Status visível no período: pendente, autorizada, rejeitada (com motivo e opção de corrigir e reenviar).
- Cancelamento dentro do prazo legal volta o período para **Fechado**.
- Ambiente de homologação configurável; a primeira emissão em produção só depois de uma nota autorizada em homologação.
- Campos de IBS/CBS a partir de 2027-01-01, via `TaxRegime`.

### Critérios de aceite

- [ ] Falha de comunicação faz nova tentativa sem emitir nota duplicada (idempotência pelo número da DPS).
- [ ] Requisição e resposta ficam registradas em `invoices`.
- [ ] Testes de contrato rodam contra `FakeIssuer` e `NfseNacionalApiIssuer` (homologação).

## Fora de escopo

Envio automático da nota por e-mail ao cliente (pode virar parte de [F-15](F-15-lembretes.md) ou funcionalidade própria).
