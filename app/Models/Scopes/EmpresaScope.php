<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Filtra automaticamente por empresa_id do usuário autenticado do painel da
 * empresa. Não filtra nada quando não há usuário autenticado com empresa_id
 * (app do cliente, admin da plataforma, comandos artisan) — esses contextos
 * precisam escopar manualmente, ver skill tenant-scoping.
 */
class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user !== null && $user->empresa_id !== null) {
            $builder->where($model->qualifyColumn('empresa_id'), $user->empresa_id);
        }
    }
}
