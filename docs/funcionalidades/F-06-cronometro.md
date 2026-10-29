# F-06 — Cronômetro

| | |
|---|---|
| **Status** | ✅ Entregue (v0.4) |
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
- Barra fixa no topo do conteúdo em todas as telas menos Hoje enquanto houver cronômetro rodando: cliente, tempo decorrido, botão parar (abre a confirmação em Hoje). A navegação inferior chega com o F-11.
- Parar abre a confirmação com descrição e referência; salvar transforma em lançamento comum.
- "Ajustar início" permite voltar o horário de início.
- Descartar cronômetro, com confirmação.
- Cronômetro rodando há mais de 12 h pede o horário real do fim ao parar.
- Parar antes de completar um minuto é recusado: continue ou descarte.

## Critérios de aceite

- [x] Só um cronômetro por vez (RN-14).
- [x] Recarregar a página ou abrir em outro aparelho mostra o mesmo cronômetro e o tempo correto.
- [x] Ao parar, valem as regras de duração e sobreposição.

## Notas técnicas

## Segurança

| Ameaça | Controles (`SEC-xx`) | Teste |
|---|---|---|
| Dois cronômetros iniciados ao mesmo tempo em dois aparelhos | A06, RN-14 | Coluna gerada `running_user_id` com índice único; a violação vira a mesma recusa (`TimerTest`) |
| Cronômetro de outra pessoa | SEC-03 | Os casos de uso só agem sobre o cronômetro do próprio ator |
| Usuário sem permissão age pela chamada direta ao Livewire | SEC-02, SEC-46 | `AccessDenied` (403) em cada caso de uso |

Usa a permissão `time.entry.create`.

## Notas técnicas

Casos de uso: `StartTimer`, `StopTimer`, `AdjustTimerStart`, `DiscardTimer`. Um cronômetro rodando ocupa do início até o futuro na checagem de sobreposição (RN-03). Componente `TimerBar` no layout; contador em `ui.elapsed` (Alpine, calculado a partir do início).
