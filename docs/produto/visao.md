# Visão do produto

## Problema

As horas de trabalho para clientes são controladas numa planilha do Google Sheets com uma aba por mês. Ela serve para emitir a nota fiscal, mas tem regras de cálculo inconsistentes entre os meses, o relatório enviado ao cliente não bate com o valor cobrado, e criar um mês novo exige mexer em fórmulas. Lançar horas pelo celular também é ruim.

## Objetivo

Um sistema web, pensado primeiro para o celular, onde:

1. lançar uma hora trabalhada leva poucos segundos (cronômetro ou lançamento manual);
2. o valor do mês é calculado por uma regra única, testada e configurável por cliente;
3. o mês fechado gera um relatório que bate exatamente com a nota;
4. no futuro, a NFS-e é emitida a partir do período fechado, sem redigitar nada.

## Público

Uma empresa prestadora de serviços (LTDA, Simples Nacional, Canoas/RS) que cobra clientes por hora:

- **Administrador:** o dono da empresa. Usa tudo e decide o que cada usuário acessa.
- **Usuários convidados:** colaboradores que lançam horas, um contador que só consulta o faturamento, etc. Cada um vê apenas os menus, botões, campos e textos liberados pelo administrador ([F-17](../funcionalidades/F-17-usuarios-e-permissoes.md)).
- **Clientes** não acessam o sistema, exceto por link de leitura no futuro ([F-13](../funcionalidades/F-13-link-publico-do-relatorio.md)).

## Princípios

- **Console profissional.** Interface no estilo de console de plataforma, densa e sóbria, sem cara de dashboard genérico ([linguagem visual](../design/linguagem-visual.md)).
- **Funciona no celular.** As tarefas do dia a dia (cronômetro, lançamento) são confortáveis com uma mão, em 390 px.
- **Acesso sob medida.** O que a pessoa não pode usar, ela não vê, e o servidor também bloqueia.
- **Qualidade verificável.** 100% de cobertura de testes e análise estática no nível máximo desde o início.
- **Uma regra, um lugar.** Cálculo de valores vive em um único serviço do domínio, coberto por testes.
- **O passado não muda.** Período fechado guarda os números com que foi fechado.
- **Pequeno e barato de manter.** Uma aplicação, um banco em arquivo, um servidor.
- **Preparado para a nota fiscal.** O domínio não depende de um provedor de NFS-e específico.

## Origem: a planilha

A planilha "Controle de Horas.xlsx" tinha três abas de dados (agosto a outubro de 2026, cliente WIS, R$ 60/h). A análise encontrou os problemas abaixo; cada um tem a regra que o resolve.

| # | Problema na planilha | Como o sistema resolve |
|---|---|---|
| 1 | Outubro somava as horas reais; os outros meses partiam dos pontos arredondados | Cálculo único no domínio — [RN-06](regras-de-negocio.md#rn-06--arredondamento) |
| 2 | Pontos arredondados para 0,1 (1,5 min), sem sentido para a regra de 15 min | Ponto inteiro de 15 min — [RN-01](regras-de-negocio.md#rn-01--ponto) |
| 3 | Relatório Mensal somava horas reais; aba somava pontos → valores diferentes | Relatório lê o snapshot do fechamento — [RN-11](regras-de-negocio.md#rn-11--snapshot-do-fechamento) |
| 4 | Mês novo exigia criar aba e ajustar fórmulas do Resumo | Período derivado das datas — [RN-08](regras-de-negocio.md#rn-08--período) |
| 5 | Trabalho após a meia-noite geraria duração negativa | Início e fim com data e hora — [RN-02](regras-de-negocio.md#rn-02--duração) |
| 6 | Status "Em Andamento" em todos os meses, sem relação com a nota | Estados do período — [RN-09](regras-de-negocio.md#rn-09--estados-do-período) |
| 7 | Limite de linhas em validações e tabelas | Não se aplica |
| 8 | Dependência do Google Sheets (FILTER, INDIRECT) | Relatório e exportação próprios — [F-09](../funcionalidades/F-09-relatorio-do-periodo.md) |
| 9 | Valores com erro de ponto flutuante (`24.99999999`) | Centavos e minutos inteiros — [RN-07](regras-de-negocio.md#rn-07--representação-de-valores) |

Totais da planilha na data da análise (2026-10-28): 59,02 h reais, R$ 3.541,00 faturados.
