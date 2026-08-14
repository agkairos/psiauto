<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('estoque.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('estoque.gerenciar');
    }

    public function update(User $user, Produto $produto): bool
    {
        return $user->can('estoque.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $produto);
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $user->can('estoque.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $produto);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Produto $produto): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $produto->empresa_id;
    }
}
