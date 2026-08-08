<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    public function view(User $user, Empresa $empresa): bool
    {
        return $user->empresa_id === $empresa->id;
    }

    /**
     * Cadastro de empresa nasce do fluxo de assinatura/aprovação (§23), não de
     * um usuário já vinculado a uma empresa — sem policy de create aqui.
     */
    public function update(User $user, Empresa $empresa): bool
    {
        return $user->empresa_id === $empresa->id && $user->can('empresa.gerenciar');
    }

    public function delete(User $user, Empresa $empresa): bool
    {
        return false;
    }
}
