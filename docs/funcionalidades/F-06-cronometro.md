# F-06 — Cronômetro

| | |
|---|---|
| **Status** | 📋 Planejado |
| **Versão** | v0.4 |
| **Telas** | T-02 |
| **Regras** | RN-02, RN-03, RN-14 |

## Objetivo

Registrar o trabalho enquanto ele acontece, com um toque para iniciar e outro para parar.

## Histórias

- Como prestador, quero iniciar o cronômetro escolhendo cliente e categoria e preencher a descrição depois.
- Como prestador, quero que o tempo continue contando mesmo se eu fechar o app ou bloquear o celular.
- Como prestador, quero corrigir o horário de início se esqueci de iniciar na hora.

## Comportamento

- Iniciar grava um lançamento com `ended_at` nulo no servidor. O contador na tela é calculado a partir do início (Alpine), não por incremento — por isso não perde tempo com a tela bloqueada.
- Barra fixa acima da navegação inferior em todas as telas enquanto houver cronômetro rodando: cliente, tempo decorrido, botão parar.
- Parar abre a confirmação com descrição e referência; salvar transforma em lançamento comum.
- "Ajustar início" permite voltar o horário de início.
- Descartar cronômetro, com confirmação.
- Cronômetro rodando há mais de 12 h pede revisão ao parar.

## Critérios de aceite

- [ ] Só um cronômetro por vez (RN-14).
- [ ] Recarregar a página ou abrir em outro aparelho mostra o mesmo cronômetro e o tempo correto.
- [ ] Ao parar, valem as regras de duração e sobreposição.

## Notas técnicas

Casos de uso: `StartTimer`, `StopTimer`, `AdjustTimerStart`, `DiscardTimer`.
