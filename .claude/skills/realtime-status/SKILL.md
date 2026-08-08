---
name: realtime-status
description: Use ao implementar ou alterar qualquer fluxo que precise refletir mudança de estado em tempo real na PsiAuto - painel do dia e fila, status de ordem de serviço, orçamento pronto/aprovado, posição na fila do app do cliente. Cobre o padrão de broadcast via Laravel Reverb entre painel da empresa, app do cliente e o backend.
---

# Tempo real com Laravel Reverb na PsiAuto

Três telas dependem de atualização imediata sem reload: painel do dia/fila (§06),
acompanhamento do veículo no app do cliente (§20) e aviso de orçamento pronto
(§08/§12).

## Padrão de canal

- Canal privado por empresa para o painel interno:
  `private-empresa.{empresaId}.painel-dia` — broadcasta mudanças de etapa de OS,
  fila e ordem de atendimento. Autorizar no `routes/channels.php` checando que o
  usuário pertence àquela empresa (mesma regra de `tenant-scoping`).
- Canal privado por veículo/atendimento para o cliente:
  `private-atendimento.{ordemServicoId}` — o cliente só recebe eventos do próprio
  atendimento. Autorizar checando que o usuário autenticado é o dono do veículo/OS.
- Não usar canal público para nada que exponha etapa de atendimento, fila ou preço —
  são dados que identificam cliente/empresa indiretamente.

## Eventos a broadcastar (mínimo)

- `OrdemServicoEtapaAlterada` — dispara ao mudar etapa (aguardando aprovação, em
  execução, aguardando peça, em teste, pronto, entregue). Consumido pelo painel do
  dia (reordena fila) e pelo app do cliente (atualiza acompanhamento).
- `FilaPosicaoAlterada` — dispara quando a ordem de atendimento de uma posição
  (box/elevador/mecânico) muda, para recalcular "quantos veículos estão à frente"
  no app do cliente.
- `OrcamentoProntoParaAprovacao` — dispara ao enviar orçamento ao cliente; também
  dispara e-mail (job assíncrono via Horizon, não bloquear a request).
- `OrcamentoAprovado` — dispara quando o cliente aprova item a item; o painel da
  empresa deve refletir a aprovação sem reload para liberar a execução.

## Implementação

- Eventos implementam `ShouldBroadcast` (fila assíncrona via Horizon — nunca
  `ShouldBroadcastNow` em produção, para não travar a request no laudo/checklist).
- Payload do evento deve ser mínimo (ids + status), não o model inteiro — o
  frontend Inertia/Vue busca o resto por uma request normal se precisar, evitando
  vazar campos sensíveis (preço de custo, margem) por um canal que o cliente também
  escuta.
- No Vue, escutar com `laravel-echo` configurado para Reverb; desinscrever do canal
  no `onUnmounted` para não acumular listeners ao navegar entre páginas Inertia.

## Ao adicionar uma nova mudança de estado

1. Ela precisa aparecer sem reload em alguma tela de §06, §08, §12 ou §20? Se não,
   não crie evento de broadcast — é overhead desnecessário.
2. O canal é privado e a autorização em `channels.php` reflete `tenant-scoping`?
3. O payload evita campos sensíveis a segmento (custo, margem, comissão)?
4. O disparo do e-mail relacionado (se houver) é um job separado, não síncrono no
   mesmo request que gera o evento?
