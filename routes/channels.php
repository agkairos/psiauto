<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// §06 — Painel do dia/fila. Um canal por empresa (não por unidade): a tela
// filtra por unidade no front, mas a autorização só precisa garantir que o
// usuário pertence àquela empresa (mesma regra de tenant-scoping).
Broadcast::channel('empresa.{empresaId}.painel-dia', function ($user, $empresaId) {
    return $user->empresa_id !== null && (int) $user->empresa_id === (int) $empresaId;
});
