<?php

namespace App\Policies;

use App\Models\Comissao;
use App\Models\User;

class ComissaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financeiro.ver');
    }

    public function update(User $user, Comissao $comissao): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $comissao);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Comissao $comissao): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $comissao->empresa_id;
    }
}
