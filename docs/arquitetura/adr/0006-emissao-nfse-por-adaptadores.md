# ADR-0006 — Emissão de NFS-e por porta e adaptadores

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-28 |

## Contexto

O prestador é LTDA no Simples Nacional, em Canoas/RS. Pela Resolução CGSN nº 191/2026, ME e EPP do Simples devem emitir NFS-e pelo **Emissor Nacional** (site ou API) a partir de 2026-11-01. A prefeitura de Canoas mantém sistema próprio (INFISC) para os demais contribuintes. Em 2027-01-01 entram os campos de IBS/CBS para o Simples. Detalhes em [fiscal/nfse.md](../../fiscal/nfse.md).

## Decisão

- O domínio define a porta `InvoiceIssuer` (emitir e consultar) e `CancelsInvoices` (cancelar), recebendo um `InvoiceRequest` montado por `InvoiceRequestFactory` a partir do período fechado.
- Adaptadores, em ordem de entrega:
  1. `AssistedManualIssuer` (v1.1) — mostra os dados prontos para copiar no site do Emissor Nacional e recebe XML/PDF anexados.
  2. `NfseNacionalApiIssuer` (v1.2) — gera a DPS, assina com certificado A1 e envia à API nacional. Homologação antes de produção.
  3. `FakeIssuer` — testes.
- Regras tributárias da nota ficam em `TaxRegime` (Strategy): `Simples2026`, `Simples2027IbsCbs`.
- Emissão roda em job na fila, com nova tentativa e registro de requisição e resposta.
- O adaptador ativo é escolhido por configuração.

## Alternativas consideradas

- **Integrar com o INFISC de Canoas** — não atende à obrigação do Simples a partir de 2026-11-01.
- **Intermediário (Focus NFe, PlugNotas, NFE.io)** — integração mais simples, com mensalidade. Fica como adaptador alternativo, se a API nacional se mostrar trabalhosa demais.

## Consequências

- Etapa assistida entrega valor rápido, sem integração.
- O certificado A1 passa a ser dado sensível do sistema: guardado criptografado, fora do repositório, com senha em variável de ambiente.
- Mudança de lei vira nova implementação de `TaxRegime`, sem tocar no restante.
