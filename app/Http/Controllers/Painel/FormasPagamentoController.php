<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarFormaPagamentoRequest;
use App\Models\FormaPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FormasPagamentoController extends Controller
{
    public function store(SalvarFormaPagamentoRequest $request): RedirectResponse
    {
        FormaPagamento::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Forma de pagamento criada.');
    }

    public function update(SalvarFormaPagamentoRequest $request, FormaPagamento $formaPagamento): RedirectResponse
    {
        $formaPagamento->update($request->validated());

        return back()->with('sucesso', 'Forma de pagamento atualizada.');
    }

    public function destroy(FormaPagamento $formaPagamento): RedirectResponse
    {
        Gate::authorize('delete', $formaPagamento);

        $formaPagamento->update(['ativa' => false]);

        return back()->with('sucesso', 'Forma de pagamento desativada.');
    }
}
