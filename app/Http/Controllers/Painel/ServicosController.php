<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarServicoRequest;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServicosController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Servico::class);

        // EmpresaScope (via BelongsToEmpresa no model Servico) já filtra por
        // empresa do usuário autenticado.
        $servicos = Servico::query()->orderBy('nome')->get();

        return Inertia::render('Painel/Servicos/Index', [
            'servicos' => $servicos,
        ]);
    }

    public function store(SalvarServicoRequest $request): RedirectResponse
    {
        Servico::create([
            ...$request->dadosParaSalvar(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Serviço criado.');
    }

    public function update(SalvarServicoRequest $request, Servico $servico): RedirectResponse
    {
        $servico->update($request->dadosParaSalvar());

        return back()->with('sucesso', 'Serviço atualizado.');
    }

    public function destroy(Servico $servico): RedirectResponse
    {
        Gate::authorize('delete', $servico);

        $servico->delete();

        return back()->with('sucesso', 'Serviço removido.');
    }
}
