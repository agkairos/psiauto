<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContasReceberController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ContaReceber::class);

        $contas = ContaReceber::query()
            ->with([
                'cliente:id,nome',
                'unidade:id,nome',
                'formaPagamento:id,nome',
                'ordemServico:id,veiculo_id',
                'ordemServico.veiculo:id,placa',
                'parcelas.recebimentos.formaPagamento:id,nome',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Painel/ContasReceber/Index', [
            'contas' => $contas,
            'formasPagamento' => FormaPagamento::query()->where('ativa', true)->orderBy('nome')->get(),
        ]);
    }
}
