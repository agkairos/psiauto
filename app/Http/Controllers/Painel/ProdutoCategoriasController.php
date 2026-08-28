<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarProdutoCategoriaRequest;
use App\Models\ProdutoCategoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProdutoCategoriasController extends Controller
{
    public function store(SalvarProdutoCategoriaRequest $request): RedirectResponse
    {
        ProdutoCategoria::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Categoria criada.');
    }

    public function update(SalvarProdutoCategoriaRequest $request, ProdutoCategoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return back()->with('sucesso', 'Categoria atualizada.');
    }

    public function destroy(ProdutoCategoria $categoria): RedirectResponse
    {
        Gate::authorize('delete', $categoria);

        // Produtos vinculados ficam sem categoria (nullOnDelete na FK) — não
        // faz sentido bloquear a remoção só porque tem produto usando.
        $categoria->delete();

        return back()->with('sucesso', 'Categoria removida.');
    }
}
