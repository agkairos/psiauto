<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarRecursoRequest;
use App\Models\Recurso;
use App\Models\Servico;
use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecursosController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Recurso::class);

        $recursos = Recurso::query()
            ->with(['unidade:id,nome', 'servicos:id,nome', 'bloqueios'])
            ->orderBy('nome')
            ->get();

        return Inertia::render('Painel/Recursos/Index', [
            'recursos' => $recursos,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
            'servicos' => Servico::query()->select('id', 'nome', 'segmento')->orderBy('nome')->get(),
        ]);
    }

    public function store(SalvarRecursoRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $recurso = Recurso::create([
            'empresa_id' => Auth::user()->empresa_id,
            'unidade_id' => $dados['unidade_id'],
            'nome' => $dados['nome'],
            'grade_semanal' => $dados['grade_semanal'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $recurso->servicos()->sync($dados['servicos'] ?? []);

        return back()->with('sucesso', 'Recurso criado.');
    }

    public function update(SalvarRecursoRequest $request, Recurso $recurso): RedirectResponse
    {
        $dados = $request->validated();

        $recurso->update([
            'unidade_id' => $dados['unidade_id'],
            'nome' => $dados['nome'],
            'grade_semanal' => $dados['grade_semanal'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $recurso->servicos()->sync($dados['servicos'] ?? []);

        return back()->with('sucesso', 'Recurso atualizado.');
    }

    public function destroy(Recurso $recurso): RedirectResponse
    {
        Gate::authorize('delete', $recurso);

        $recurso->delete();

        return back()->with('sucesso', 'Recurso removido.');
    }
}
