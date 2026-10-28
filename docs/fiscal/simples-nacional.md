# Simples Nacional e Fator R

Base para [F-14 Fator R e estimativa de impostos](../funcionalidades/F-14-fator-r-e-impostos.md). O sistema **estima**; o cálculo oficial e o enquadramento são responsabilidade do contador.

## Anexo III ou V

Serviços de desenvolvimento de software ficam, em regra, sujeitos ao **Fator R**:

```
Fator R = (folha de salários + pró-labore + encargos dos últimos 12 meses) ÷ receita bruta dos últimos 12 meses (RBT12)
```

| Fator R | Anexo | Alíquota nominal da 1ª faixa |
|---|---|---|
| ≥ 28% | III | 6% |
| < 28% | V | 15,5% |

O enquadramento é refeito a cada mês de apuração.

## O que o sistema faz

- Soma a receita mensal a partir dos períodos **faturados** (data de emissão da nota) e de receitas informadas manualmente.
- Recebe o pró-labore e a folha de cada mês (`monthly_payrolls`).
- Mostra o Fator R dos últimos 12 meses, o anexo resultante e quanto de pró-labore faltaria para chegar a 28%.
- Estima a alíquota efetiva e o DAS do mês com as tabelas vigentes, guardadas como configuração versionada por ano.

## Pendências

- Conferir as tabelas oficiais da LC 123/2006 (faixas, alíquotas nominais e parcelas a deduzir) antes de implementar; fontes comerciais divergem.
- Confirmar com o contador se o CNAE principal está sujeito ao Fator R ou se enquadra direto no Anexo III.

## Fontes

- [Agilize — Simples Nacional para software: Anexo III ou V?](https://agilize.com.br/artigos/?p=4180)
- Lei Complementar nº 123/2006 (tabelas dos anexos)
