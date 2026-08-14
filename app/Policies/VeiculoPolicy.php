<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Veiculo;

class VeiculoPolicy
{
    public function create(User $user): bool
    {
        return $user->can('clientes.gerenciar');
    }

    public function update(User $user, Veiculo $veiculo): bool
    {
        return $user->can('clientes.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $veiculo);
    }

    public function delete(User $user, Veiculo $veiculo): bool
    {
        return $user->can('clientes.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $veiculo);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Veiculo $veiculo): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $veiculo->empresa_id;
    }
}
