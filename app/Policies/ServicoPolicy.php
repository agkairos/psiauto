<?php

namespace App\Policies;

use App\Models\Servico;
use App\Models\User;

class ServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('servicos.ver');
    }

    public function view(User $user, Servico $servico): bool
    {
        return $user->can('servicos.ver') && $this->pertenceAEmpresaDoUsuario($user, $servico);
    }

    public function create(User $user): bool
    {
        return $user->can('servicos.gerenciar');
    }

    /**
     * Também libera para quem só tem `servicos.editar_preco` (sem
     * `servicos.gerenciar`) — o controller/FormRequest restringe quais
     * campos essa pessoa pode de fato alterar. Ver
     * App\Http\Requests\SalvarServicoRequest::soPodeAlterarPreco().
     */
    public function update(User $user, Servico $servico): bool
    {
        return ($user->can('servicos.gerenciar') || $user->can('servicos.editar_preco'))
            && $this->pertenceAEmpresaDoUsuario($user, $servico);
    }

    public function delete(User $user, Servico $servico): bool
    {
        return $user->can('servicos.gerenciar') && $this->pertenceAEmpresaDoUsuario($user, $servico);
    }

    private function pertenceAEmpresaDoUsuario(User $user, Servico $servico): bool
    {
        return $user->empresa_id !== null && $user->empresa_id === $servico->empresa_id;
    }
}
