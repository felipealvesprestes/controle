# Glossário

| Termo | Definição |
|---|---|
| **Prestador** | A empresa que presta o serviço e emite a nota. |
| **Administrador** | Usuário com acesso total, que gerencia usuários, perfis e permissões. |
| **Usuário** | Pessoa convidada pelo administrador, com acesso definido por perfis e exceções. |
| **Permissão** | Chave do catálogo (ex.: `billing.period.close`) que libera um menu, página, ação, campo, conteúdo ou escopo de dados. |
| **Perfil** | Conjunto nomeado de permissões atribuído a usuários (ex.: Colaborador, Contador). |
| **Exceção** | Permissão permitida ou negada para um usuário específico, por cima dos perfis. |
| **Cliente** | Quem contrata o serviço e recebe a nota (tomador, na linguagem da NFS-e). |
| **Lançamento** | Um intervalo de trabalho: cliente, categoria, descrição, início e fim. |
| **Cronômetro** | Lançamento em andamento, com início e sem fim. Vira lançamento comum ao parar. |
| **Categoria** | Classificação do lançamento (Desenvolvimento, Reunião, Planejamento...). Pode ser faturável ou não. |
| **Referência** | Código externo da tarefa, como `MRP-62` no Linear. |
| **Ponto** | Unidade de cobrança equivalente a 15 minutos. Sempre inteiro. |
| **Taxa horária** | Valor cobrado por hora de um cliente, com data de início de vigência. |
| **Valor do ponto** | Taxa horária ÷ 4. |
| **Política de arredondamento** | Regra que converte minutos em pontos: por período, por lançamento ou exato. |
| **Período** | Conjunto de lançamentos de um cliente em um mês, que vira uma cobrança. |
| **Fechamento** | Ação que congela o período e grava seus totais (snapshot). |
| **Snapshot** | Cópia dos números do período no momento do fechamento: minutos, pontos, taxa, política e valor. |
| **NFS-e** | Nota Fiscal de Serviço eletrônica. |
| **Emissor Nacional** | Sistema federal de emissão de NFS-e (site e API), obrigatório para ME/EPP do Simples a partir de 2026-11-01. |
| **DPS** | Declaração de Prestação de Serviço: o documento enviado ao Emissor Nacional que gera a NFS-e. |
| **Fator R** | Folha + pró-labore dos últimos 12 meses ÷ receita bruta do mesmo período. Define Anexo III ou V do Simples. |
