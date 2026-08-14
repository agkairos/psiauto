<?php

namespace App\Policies;

use App\Models\ContaPagar;
use App\Models\User;

class ContaPagarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financeiro.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('financeiro.gerenciar');
    }

    public function update(User $user, ContaPagar $contaPagar): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $contaPagar);
    }

    public function delete(User $user, ContaPagar $contaPagar): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $contaPagar);
    }

    private function pertenceAEmpresaDoUsuario(User $user, ContaPagar $contaPagar): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $contaPagar->empresa_id;
    }
}
