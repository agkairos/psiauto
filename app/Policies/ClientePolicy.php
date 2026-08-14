<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clientes.ver');
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->can('clientes.ver') && $this->pertenceAEmpresaDoUsuario($user, $cliente);
    }

    public function create(User $user): bool
    {
        return $user->can('clientes.gerenciar');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->can('clientes.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $cliente);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->can('clientes.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $cliente);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Cliente $cliente): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $cliente->empresa_id;
    }
}
