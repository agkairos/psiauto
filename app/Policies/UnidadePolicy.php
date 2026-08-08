<?php

namespace App\Policies;

use App\Models\Unidade;
use App\Models\User;

class UnidadePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Unidade $unidade): bool
    {
        return $this->pertenceAEmpresaDoUsuario($user, $unidade);
    }

    public function create(User $user): bool
    {
        return $user->can('empresa.gerenciar');
    }

    public function update(User $user, Unidade $unidade): bool
    {
        return $user->can('empresa.gerenciar')
            && $this->pertenceAEmpresaDoUsuario($user, $unidade);
    }

    public function delete(User $user, Unidade $unidade): bool
    {
        return $user->can('empresa.gerenciar')
            && $this->pertenceAEmpresaDoUsuario($user, $unidade);
    }

    public function restore(User $user, Unidade $unidade): bool
    {
        return $this->delete($user, $unidade);
    }

    public function forceDelete(User $user, Unidade $unidade): bool
    {
        return false;
    }

    /**
     * Posse: além da permissão, o usuário só mexe em unidades da própria
     * empresa. Ver skill tenant-scoping.
     */
    private function pertenceAEmpresaDoUsuario(User $user, Unidade $unidade): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $unidade->empresa_id;
    }
}
