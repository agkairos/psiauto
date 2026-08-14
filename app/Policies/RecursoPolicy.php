<?php

namespace App\Policies;

use App\Models\Recurso;
use App\Models\User;

class RecursoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agenda.gerenciar');
    }

    public function view(User $user, Recurso $recurso): bool
    {
        return $user->can('agenda.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $recurso);
    }

    public function create(User $user): bool
    {
        return $user->can('agenda.gerenciar');
    }

    public function update(User $user, Recurso $recurso): bool
    {
        return $user->can('agenda.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $recurso);
    }

    public function delete(User $user, Recurso $recurso): bool
    {
        return $user->can('agenda.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $recurso);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Recurso $recurso): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $recurso->empresa_id;
    }
}
