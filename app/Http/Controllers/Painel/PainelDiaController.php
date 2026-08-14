<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PainelDiaController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Agendamento::class);

        $unidadeId = $request->integer('unidade_id') ?: null;

        $agendamentos = Agendamento::query()
            ->whereDate('data', today())
            ->whereNotIn('status', Agendamento::STATUS_QUE_LIBERAM_HORARIO)
            ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId))
            ->with(['cliente:id,nome', 'veiculo:id,placa', 'servico:id,nome', 'recurso:id,nome'])
            ->orderBy('hora_inicio')
            ->get();

        return Inertia::render('Painel/PainelDia/Index', [
            'agendamentos' => $agendamentos,
            'unidadeId' => $unidadeId,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
        ]);
    }
}
