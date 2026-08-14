<?php

namespace App\Policies;

use App\Models\PedidoPeca;
use App\Models\User;

class PedidoPecaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orcamento_pecas.gerenciar');
    }

    public function create(User $user): bool
    {
        return $user->can('orcamento_pecas.gerenciar');
    }

    public function update(User $user, PedidoPeca $pedidoPeca): bool
    {
        return $user->can('orcamento_pecas.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $pedidoPeca);
    }

    private function pertenceAEmpresaDoUsuario(User $user, PedidoPeca $pedidoPeca): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $pedidoPeca->empresa_id;
    }
}
