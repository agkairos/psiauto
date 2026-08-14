<?php

namespace App\Policies;

use App\Models\ContaReceber;
use App\Models\User;

class ContaReceberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financeiro.ver');
    }

    public function update(User $user, ContaReceber $contaReceber): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $contaReceber);
    }

    private function pertenceAEmpresaDoUsuario(User $user, ContaReceber $contaReceber): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $contaReceber->empresa_id;
    }
}
