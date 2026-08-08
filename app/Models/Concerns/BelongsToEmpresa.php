<?php

namespace App\Models\Concerns;

use App\Models\Scopes\EmpresaScope;

/**
 * Aplica o EmpresaScope a todo model operacional (agendamento, OS, orçamento,
 * cliente, veículo, produto, financeiro, nota fiscal). Ver skill tenant-scoping.
 */
trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::creating(function ($model) {
            $user = auth()->user();

            if ($model->empresa_id === null && $user !== null && $user->empresa_id !== null) {
                $model->empresa_id = $user->empresa_id;
            }
        });
    }
}
