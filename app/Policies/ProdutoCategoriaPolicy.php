<?php

namespace App\Policies;

use App\Models\ProdutoCategoria;
use App\Models\User;

class ProdutoCategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('estoque.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('estoque.gerenciar');
    }

    public function update(User $user, ProdutoCategoria $produtoCategoria): bool
    {
        return $user->can('estoque.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $produtoCategoria);
    }

    public function delete(User $user, ProdutoCategoria $produtoCategoria): bool
    {
        return $user->can('estoque.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $produtoCategoria);
    }

    private function pertenceAEmpresaDoUsuario(User $user, ProdutoCategoria $produtoCategoria): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $produtoCategoria->empresa_id;
    }
}
