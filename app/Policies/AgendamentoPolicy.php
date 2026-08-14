<?php

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agendamentos.ver');
    }

    public function view(User $user, Agendamento $agendamento): bool
    {
        return $user->can('agendamentos.ver') && $this->pertenceAEmpresaDoUsuario($user, $agendamento);
    }

    public function create(User $user): bool
    {
        return $user->can('agendamentos.gerenciar');
    }

    public function update(User $user, Agendamento $agendamento): bool
    {
        return $user->can('agendamentos.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $agendamento);
    }

    public function delete(User $user, Agendamento $agendamento): bool
    {
        return $user->can('agendamentos.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $agendamento);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Agendamento $agendamento): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $agendamento->empresa_id;
    }
}
