# T-10 — Configurações

| | |
|---|---|
| **Status** | 🚧 Em andamento (Padrões de cobrança e Dados da empresa na v0.3) |
| **Rota** | `/settings/*` |
| **Funcionalidades** | F-01, F-02, F-07, F-12, F-14 |
| **Acesso** | Mais → Configurações |

## Propósito

Agrupar tudo que se configura raramente. Cada item abre uma subpágina.

## Wireframe (mobile, 390 px)

```
┌──────────────────────────────┐
│ ← Configurações              │
├──────────────────────────────┤
│ COBRANÇA                     │
│ Taxa padrão       R$ 60/h  › │
│ Arredondamento    Período  › │
│ Meta mensal (v1.3)         › │
│                              │
│ EMPRESA                      │
│ Dados da empresa           › │
│ Nota fiscal (v1.1)         › │
│ Certificado A1 (v1.2)      › │
│ Pró-labore e folha (v1.3)  › │
│                              │
│ DADOS                        │
│ Importar planilha          › │
│ Exportar tudo (CSV)        › │
│                              │
│ CONTA                        │
│ Perfil e senha             › │
│ Autenticação em 2 fatores  › │
│ Aparência                  › │
│ Sair                         │
└──────────────────────────────┘
```

## Subpáginas

| Item | Funcionalidade | Observação |
|---|---|---|
| Taxa padrão, Arredondamento | F-02 | `/settings/billing` (Padrões de cobrança). Exemplo ao vivo do arredondamento |
| Dados da empresa, Nota fiscal | F-02, F-12 | `/settings/company` (Dados da empresa) na v0.3, com busca de endereço pelo CEP; Códigos de serviço confirmados com o contador |
| Certificado A1 | F-12 | Envio, validade, teste em homologação |
| Pró-labore e folha | F-14 | Tabela mês a mês |
| Importar planilha | F-07 | Envio, prévia, confirmação |
| Perfil, 2FA, Aparência | F-01 | Páginas existentes do starter kit, traduzidas |
