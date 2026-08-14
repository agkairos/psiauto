<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarVeiculoRequest;
use App\Models\Cliente;
use App\Models\Veiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VeiculosController extends Controller
{
    public function store(SalvarVeiculoRequest $request, Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $cliente->veiculos()->create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Veículo cadastrado.');
    }

    public function update(SalvarVeiculoRequest $request, Veiculo $veiculo): RedirectResponse
    {
        $veiculo->update($request->validated());

        return back()->with('sucesso', 'Veículo atualizado.');
    }

    public function destroy(Veiculo $veiculo): RedirectResponse
    {
        Gate::authorize('delete', $veiculo);

        $veiculo->delete();

        return back()->with('sucesso', 'Veículo removido.');
    }
}
