<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarClienteRequest;
use App\Models\Cliente;
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
                    $q->where('nome', 'ilike', "%{$busca}%")
                        ->orWhere('telefone', 'ilike', "%{$busca}%")
                        ->orWhereHas('veiculos', fn ($vq) => $vq->where('placa', 'ilike', "%{$busca}%"));
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
