<?php

namespace App\Policies;

use App\Models\FormaPagamento;
use App\Models\User;

class FormaPagamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financeiro.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('financeiro.gerenciar');
    }

    public function update(User $user, FormaPagamento $formaPagamento): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $formaPagamento);
    }

    public function delete(User $user, FormaPagamento $formaPagamento): bool
    {
        return $user->can('financeiro.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $formaPagamento);
    }

    private function pertenceAEmpresaDoUsuario(User $user, FormaPagamento $formaPagamento): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $formaPagamento->empresa_id;
    }
}
