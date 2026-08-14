<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarContaPagarRequest;
use App\Models\ContaPagar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContasPagarController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ContaPagar::class);

        $contas = ContaPagar::query()
            ->with(['unidade:id,nome', 'pagamentos'])
            ->orderBy('data_vencimento')
            ->paginate(20);

        return Inertia::render('Painel/ContasPagar/Index', [
            'contas' => $contas,
        ]);
    }

    public function store(SalvarContaPagarRequest $request): RedirectResponse
    {
        ContaPagar::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
            'criado_por' => Auth::id(),
        ]);

        return back()->with('sucesso', 'Conta a pagar criada.');
    }

    public function update(SalvarContaPagarRequest $request, ContaPagar $contaPagar): RedirectResponse
    {
        $contaPagar->update($request->validated());

        return back()->with('sucesso', 'Conta a pagar atualizada.');
    }

    public function destroy(ContaPagar $contaPagar): RedirectResponse
    {
        Gate::authorize('delete', $contaPagar);

        $contaPagar->delete();

        return back()->with('sucesso', 'Conta a pagar removida.');
    }
}
