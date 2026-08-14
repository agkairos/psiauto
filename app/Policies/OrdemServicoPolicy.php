<?php

namespace App\Policies;

use App\Models\OrdemServico;
use App\Models\User;

class OrdemServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('os.ver');
    }

    public function view(User $user, OrdemServico $os): bool
    {
        return $user->can('os.ver') && $this->pertenceAEmpresaDoUsuario($user, $os);
    }

    public function create(User $user): bool
    {
        return $user->can('os.gerenciar');
    }

    public function update(User $user, OrdemServico $os): bool
    {
        return $user->can('os.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $os);
    }

    public function delete(User $user, OrdemServico $os): bool
    {
        return $user->can('os.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $os);
    }

    private function pertenceAEmpresaDoUsuario(User $user, OrdemServico $os): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $os->empresa_id;
    }
}
