<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarAplicacaoProdutoRequest;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AplicacoesProdutoController extends Controller
{
    public function store(SalvarAplicacaoProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $produto->aplicacoes()->create($request->validated());

        return back()->with('sucesso', 'Aplicação adicionada.');
    }

    public function destroy(ProdutoAplicacao $aplicacao): RedirectResponse
    {
        Gate::authorize('update', $aplicacao->produto);

        $aplicacao->delete();

        return back()->with('sucesso', 'Aplicação removida.');
    }
}
