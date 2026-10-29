# F-02 — Configurações do prestador

| | |
|---|---|
| **Status** | ✅ Implementada (v0.3: Cobrança e Empresa; Nota fiscal na v1.1, Certificado na v1.2) |
| **Versão** | v0.3 |
| **Telas** | T-10 |
| **Regras** | RN-04, RN-06 |

## Objetivo

Guardar os padrões de cobrança e os dados da empresa usados no relatório e na nota.

## Histórias

- Como prestador, quero definir uma taxa padrão para não repetir em cada cliente.
- Como prestador, quero escolher a política de arredondamento padrão.
- Como prestador, quero cadastrar os dados da empresa uma vez para usar no relatório e na nota.

## Comportamento

Seções da tela:

1. **Cobrança** (`/settings/billing`): taxa horária padrão (R$, de 0,01 a 100.000,00), política de arredondamento padrão (Período, Lançamento, Exato) com exemplo ao vivo calculado pelo domínio ("1h07 → 5 pontos → R$ 75,00").
2. **Empresa** (`/settings/company`): razão social, CNPJ e inscrição municipal obrigatórios (prestador de serviço não tem inscrição estadual, e o sistema não pede); nome fantasia e endereço (CEP, logradouro, número, complemento, bairro, cidade, UF). Regime tributário fixo em Simples Nacional — ME/EPP. Ao completar os 8 dígitos do CEP, logradouro, bairro, cidade e UF são preenchidos pela ViaCEP ([ADR-0013](../arquitetura/adr/0013-busca-de-cep.md)); número e complemento ficam como estão. CEP inexistente ou serviço fora do ar mostram um aviso no campo, sem apagar o que foi digitado.
3. **Nota fiscal** (v1.1): código de tributação nacional, código NBS, CNAE, texto base da descrição da nota com variáveis (`{cliente}`, `{mes}`, `{horas}`).
4. **Certificado** (v1.2): envio do A1, validade e status.

## Critérios de aceite

- [x] CNPJ validado (dígitos verificadores), no formato numérico e no alfanumérico da Receita (a partir de julho de 2026).
- [ ] Alterar a taxa padrão não altera períodos fechados (RN-11). Garantido pelo snapshot do fechamento; o teste entra com o F-08 na v0.5.
- [x] O exemplo de arredondamento atualiza conforme a política e a taxa digitadas.
- [x] Toda alteração fica no histórico de auditoria (quem, quando, antes, depois).
- [x] O CEP preenche logradouro, bairro, cidade e UF e leva o cursor ao número.
- [x] O Painel marca "Dados da empresa" como feito quando razão social, CNPJ e inscrição municipal estão preenchidos.

## Segurança

| Ameaça | Controles (`SEC-xx`) | Teste |
|---|---|---|
| Usuário sem permissão salva pela chamada direta ao Livewire | SEC-02, SEC-46 | `AccessDenied` (403) nos casos de uso `UpdateBillingDefaults` e `UpdateCompanyDetails` |
| Taxa vazar pelo HTML ou pelo estado do Livewire para quem não pode vê-la | SEC-04, F-17 | Sem `settings.billing.rate.view`, a propriedade `rate` fica vazia e o valor aparece como `•••` |
| Campos extras ou adulterados gravados no perfil | SEC-05 | Repositório grava só os campos do DTO, depois de validados no caso de uso |
| Alteração da taxa sem rastro | SEC-41, SEC-47 | Gravação e auditoria na mesma transação |
| Busca de CEP usada para atingir outros endereços (SSRF) ou como proxy | SEC-30, SEC-48 | URL montada só com 8 dígitos, sem redirecionamento; 20 buscas por minuto por usuário (`ZipCodeLookupTest`) |
| Resposta da ViaCEP com conteúdo inesperado | SEC-24, A10 | Só textos, cortados no tamanho das colunas, UF da lista oficial; senão vira "indisponível" |
| Valor fora da coluna derruba a tela com erro 500 | A10 | `tests/Feature/Security/ProviderSettingsInputTest.php` (A-11) |

Permissões do catálogo usadas por esta funcionalidade: `settings.billing.menu`, `settings.billing.page`, `settings.billing.rate.view`, `settings.billing.edit`, `settings.company.menu`, `settings.company.page`, `settings.company.edit`.

## Notas técnicas

Registro único em `provider_profiles`, criado na primeira gravação. Enquanto não existe, a leitura devolve os valores iniciais: taxa de R$ 60,00 e política Período (`BillingDefaults::initial()`). O CNPJ é o value object `Cnpj` em `app/Domain/Shared`, reaproveitado pelo F-03.
