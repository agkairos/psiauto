<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarRecebimentoRequest;
use App\Models\Parcela;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecebimentosController extends Controller
{
    public function store(RegistrarRecebimentoRequest $request, Parcela $parcela): RedirectResponse
    {
        Gate::authorize('update', $parcela->contaReceber);

        $dados = $request->validated();

        $saldoDevedor = bcsub((string) $parcela->valor, (string) $parcela->valor_recebido, 2);
        if (bccomp((string) $dados['valor'], $saldoDevedor, 2) > 0) {
            return back()->withErrors(['valor' => 'O valor não pode passar do saldo da parcela ('.$saldoDevedor.').']);
        }

        DB::transaction(function () use ($parcela, $dados) {
            $parcela->recebimentos()->create([
                'empresa_id' => $parcela->empresa_id,
                'forma_pagamento_id' => $dados['forma_pagamento_id'] ?? null,
                'valor' => $dados['valor'],
                'data' => $dados['data'],
                'registrado_por' => Auth::id(),
            ]);

            $parcela->update([
                'valor_recebido' => bcadd((string) $parcela->valor_recebido, (string) $dados['valor'], 2),
            ]);
        });

        return back()->with('sucesso', 'Recebimento registrado.');
    }
}
