<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarProdutoRequest;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\ProdutoCategoria;
use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProdutosController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Produto::class);

        $unidades = Unidade::query()->select('id', 'nome')->orderBy('nome')->get();
        $unidadeId = $request->integer('unidade_id') ?: $unidades->first()?->id;

        $produtos = Produto::query()
            ->where('ativo', true)
            ->with([
                'movimentacoes' => fn ($q) => $q->where('unidade_id', $unidadeId),
                'aplicacoes.marca:id,nome',
                'aplicacoes.modelo:id,nome',
                'categoria:id,nome',
            ])
            ->orderBy('nome')
            ->get()
            ->map(function (Produto $produto) use ($unidadeId) {
                $produto->saldo_unidade_atual = $unidadeId ? $produto->saldoNaUnidade($unidadeId) : 0;

                return $produto;
            });

        return Inertia::render('Painel/Produtos/Index', [
            'produtos' => $produtos,
            'unidades' => $unidades,
            'unidadeId' => $unidadeId,
            'marcas' => Marca::query()->where('tipo_veiculo', 'carro')->orderBy('nome')->get(['id', 'nome']),
            'categorias' => ProdutoCategoria::query()->where('ativa', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(SalvarProdutoRequest $request): RedirectResponse
    {
        Produto::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Produto criado.');
    }

    public function update(SalvarProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $produto->update($request->validated());

        return back()->with('sucesso', 'Produto atualizado.');
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        Gate::authorize('delete', $produto);

        $produto->delete();

        return back()->with('sucesso', 'Produto removido.');
    }
}
