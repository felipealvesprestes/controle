# F-16 — Integração com o Linear

| | |
|---|---|
| **Status** | 💡 Ideia |
| **Versão** | backlog |
| **Telas** | T-04, T-02 |

## Ideia

As descrições da planilha seguem o padrão do Linear (`MRP-62/permitir-empresas-inativas...`). Com a integração:

- Ao digitar a referência (`MRP-62`), o sistema busca o título da tarefa e preenche a descrição.
- Iniciar o cronômetro a partir de uma lista das minhas tarefas em andamento.
- Referência vira link para a tarefa no relatório.

## A decidir

- Chave de API pessoal por cliente/workspace (cada cliente pode ter seu Linear).
- Seguir o padrão de porta e adaptador (`IssueTracker`), deixando espaço para Jira/GitHub.
