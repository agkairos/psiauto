<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarRecursoRequest;
use App\Models\Recurso;
use App\Models\Servico;
use App\Models\Unidade;
use App\Models\User;
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
            ->with(['unidade:id,nome', 'servicos:id,nome', 'bloqueios', 'user:id,name'])
            ->orderBy('nome')
            ->get();

        return Inertia::render('Painel/Recursos/Index', [
            'recursos' => $recursos,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
            'servicos' => Servico::query()->select('id', 'nome', 'segmento')->orderBy('nome')->get(),
            // §02 — só técnico faz sentido virar posto-pessoa; demais perfis
            // não são "capacidade agendável".
            'tecnicos' => User::query()
                ->where('empresa_id', Auth::user()->empresa_id)
                ->where('ativo', true)
                ->whereHas('roles', fn ($q) => $q->where('name', 'tecnico'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(SalvarRecursoRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $recurso = Recurso::create([
            'empresa_id' => Auth::user()->empresa_id,
            'unidade_id' => $dados['unidade_id'],
            'nome' => $this->nomeParaSalvar($dados),
            'tipo' => $dados['tipo'],
            'user_id' => $dados['user_id'] ?? null,
            'grade_semanal' => $dados['grade_semanal'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $recurso->servicos()->sync($dados['servicos'] ?? []);

        return back()->with('sucesso', 'Posto de atendimento criado.');
    }

    public function update(SalvarRecursoRequest $request, Recurso $recurso): RedirectResponse
    {
        $dados = $request->validated();

        $recurso->update([
            'unidade_id' => $dados['unidade_id'],
            'nome' => $this->nomeParaSalvar($dados),
            'tipo' => $dados['tipo'],
            'user_id' => $dados['user_id'] ?? null,
            'grade_semanal' => $dados['grade_semanal'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $recurso->servicos()->sync($dados['servicos'] ?? []);

        return back()->with('sucesso', 'Posto de atendimento atualizado.');
    }

    public function destroy(Recurso $recurso): RedirectResponse
    {
        Gate::authorize('delete', $recurso);

        $recurso->delete();

        return back()->with('sucesso', 'Posto de atendimento removido.');
    }

    // Posto-pessoa vinculado a um usuário: o nome vem sempre do usuário, pra
    // não divergir do nome de login exibido em outras telas (comissão, OS).
    private function nomeParaSalvar(array $dados): string
    {
        if ($dados['tipo'] === Recurso::TIPO_PESSOA && ! empty($dados['user_id'])) {
            return User::query()->find($dados['user_id'])?->name ?? $dados['nome'];
        }

        return $dados['nome'];
    }
}
