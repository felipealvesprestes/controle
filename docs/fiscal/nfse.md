# NFS-e

Situação levantada em 2026-10-28. **Confirmar com o contador antes de cada mudança de etapa.** Legislação muda; atualize esta página e a data quando mudar.

## Perfil do prestador

- LTDA, optante do Simples Nacional (ME/EPP)
- Município: Canoas/RS
- Serviço: desenvolvimento de software / consultoria em TI, cobrado por hora

## Onde emitir

| Data | Regra |
|---|---|
| até 2026-10-31 | Canoas aceita emissão pelo sistema próprio da prefeitura (GIF-INFISC) |
| **a partir de 2026-11-01** | ME/EPP do Simples Nacional devem emitir pelo **Emissor Nacional** — site ou API (Resolução CGSN nº 191/2026) |
| **a partir de 2027-01-01** | Regras de IBS/CBS passam a valer para optantes do Simples, incluindo destaques na nota quando cabíveis |

Pontos em aberto:

- A prefeitura de Canoas diz manter o INFISC "sem necessidade de migração para o emissor nacional", mas o comunicado da Receita não abre exceção para municípios com sistema próprio. Para ME/EPP do Simples, seguir o Emissor Nacional até orientação contrária do contador.
- Confirmar o **código de tributação nacional**, o **código NBS** e o **CNAE** usados na nota com o contador e registrar em [F-02](../funcionalidades/F-02-configuracoes-do-prestador.md).

## Como o sistema vai emitir

Ver [ADR-0006](../arquitetura/adr/0006-emissao-nfse-por-adaptadores.md) e [F-12](../funcionalidades/F-12-emissao-de-nfse.md).

| Etapa | Versão | Como |
|---|---|---|
| A — assistida | v1.1 | O sistema mostra os dados prontos (tomador, descrição, valor, códigos); a emissão é feita no site do Emissor Nacional; XML e PDF são anexados ao período |
| B — API | v1.2 | O sistema gera a DPS, assina com o certificado A1 e envia à API do Emissor Nacional |

## Dados necessários para a DPS

**Prestador:** CNPJ, inscrição municipal, regime (Simples Nacional ME/EPP), endereço.
**Tomador:** CPF/CNPJ, razão social, endereço, e-mail.
**Serviço:** código de tributação nacional, código NBS, descrição, município de prestação (Canoas), valor, retenção de ISS (sim/não).
**A partir de 2027:** campos de IBS/CBS conforme leiaute vigente.

## Certificado digital

- Certificado **A1 (.pfx)** da empresa, necessário para a API (etapa B).
- Nunca entra no repositório. Em produção fica num diretório privado fora de `public/`, criptografado com a chave da aplicação; a senha fica em variável de ambiente.
- Registrar a data de validade e criar lembrete de renovação ([F-15](../funcionalidades/F-15-lembretes.md)).

## Fontes

- [Receita Federal — NFS-e Nacional obrigatória para ME e EPP a partir de 1º/11/2026](https://www.gov.br/receitafederal/pt-br/assuntos/noticias/2026/agosto/simples-nacional-nfs-e-nacional-sera-obrigatoria-para-me-e-epp-a-partir-de-1o-de-novembro-de-2026)
- [Fenacon — NFS-e nacional para empresas do Simples é adiada para novembro](https://fenacon.org.br/reforma-tributaria/nfs-e-nacional-para-empresas-do-simples-e-adiada-para-novembro/)
- [Prefeitura de Canoas — Reforma Tributária e a emissão de NFS-e](https://www.canoas.rs.gov.br/servicos/reforma-tributaria-e-a-emissao-de-nfse/)
- [Focus NFe — NFS-e em Canoas/RS](https://focusnfe.com.br/guides/nfse/municipios-integrados/canoas-rs/)
- [Portal da NFS-e](https://www.nfse.gov.br)
