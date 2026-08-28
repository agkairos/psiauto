<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Cliente::class);

        $busca = $request->string('busca')->trim()->value();

        $clientes = Cliente::query()
            ->withCount('veiculos')
            ->with(['veiculos.marca:id,nome', 'veiculos.modelo:id,nome'])
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($q) use ($busca) {
                    // MySQL: LIKE já é case-insensitive com a collation padrão
                    // (utf8mb4_unicode_ci) — ilike era só necessário no Postgres.
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('telefone', 'like', "%{$busca}%")
                        ->orWhereHas('veiculos', fn ($vq) => $vq->where('placa', 'like', "%{$busca}%"));
                });
            })
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Painel/Clientes/Index', [
            'clientes' => $clientes,
            'busca' => $busca,
        ]);
    }

    /**
     * Busca leve em JSON pro componente reutilizável de seleção de cliente
     * (ClienteBusca.vue) — usado em qualquer formulário que precise
     * selecionar um cliente sem carregar a lista inteira de uma vez
     * (estabelecimento pode ter milhares de clientes cadastrados).
     */
    public function buscar(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Cliente::class);

        $busca = $request->string('q')->trim()->value();

        if ($busca === '' || mb_strlen($busca) < 2) {
            return response()->json([]);
        }

        $clientes = Cliente::query()
            ->where('ativo', true)
            ->with(['veiculos:id,cliente_id,placa,marca_id,modelo_id', 'veiculos.marca:id,nome', 'veiculos.modelo:id,nome'])
            ->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('telefone', 'like', "%{$busca}%")
                    ->orWhereHas('veiculos', fn ($vq) => $vq->where('placa', 'like', "%{$busca}%"));
            })
            ->orderBy('nome')
            ->limit(20)
            ->get(['id', 'nome', 'telefone']);

        return response()->json($clientes);
    }

    public function store(SalvarClienteRequest $request): RedirectResponse
    {
        Cliente::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Cliente cadastrado.');
    }

    public function update(SalvarClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return back()->with('sucesso', 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('delete', $cliente);

        $cliente->delete();

        return back()->with('sucesso', 'Cliente removido.');
    }
}
