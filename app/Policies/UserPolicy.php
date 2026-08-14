<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.gerenciar');
    }

    public function view(User $user, User $alvo): bool
    {
        return $user->can('usuarios.gerenciar') && $this->mesmaEmpresa($user, $alvo);
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.gerenciar');
    }

    public function update(User $user, User $alvo): bool
    {
        return $user->can('usuarios.gerenciar')
            && $this->mesmaEmpresa($user, $alvo)
            && ! $this->eEleMesmo($user, $alvo);
    }

    public function delete(User $user, User $alvo): bool
    {
        // "Deletar" aqui é desativar (ver UsuariosController) — nunca remove o
        // registro, histórico de autoria (OS, orçamento) depende dele.
        return $user->can('usuarios.gerenciar')
            && $this->mesmaEmpresa($user, $alvo)
            && ! $this->eEleMesmo($user, $alvo)
            && $alvo->getRoleNames()->doesntContain('proprietario');
    }

    private function mesmaEmpresa(User $user, User $alvo): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $alvo->empresa_id;
    }

    private function eEleMesmo(User $user, User $alvo): bool
    {
        return $user->id === $alvo->id;
    }
}
