<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarUnidadeRequest;
use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnidadesController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Unidade::class);

        // EmpresaScope (via BelongsToEmpresa no model Unidade) já filtra por
        // empresa do usuário autenticado — ver App\Models\Scopes\EmpresaScope.
        $unidades = Unidade::query()->orderBy('nome')->get();

        return Inertia::render('Painel/Unidades/Index', [
            'unidades' => $unidades,
        ]);
    }

    public function store(SalvarUnidadeRequest $request): RedirectResponse
    {
        Unidade::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Unidade criada.');
    }

    public function update(SalvarUnidadeRequest $request, Unidade $unidade): RedirectResponse
    {
        $unidade->update($request->validated());

        return back()->with('sucesso', 'Unidade atualizada.');
    }

    public function destroy(Unidade $unidade): RedirectResponse
    {
        Gate::authorize('delete', $unidade);

        $unidade->delete();

        return back()->with('sucesso', 'Unidade removida.');
    }
}
