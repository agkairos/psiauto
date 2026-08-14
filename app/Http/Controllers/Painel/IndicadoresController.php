<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\ContaReceber;
use App\Models\OrcamentoItem;
use App\Models\OrdemServico;
use App\Models\Recurso;
use App\Models\Unidade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IndicadoresController extends Controller
{
    private const DIAS_SEMANA = [
        1 => 'segunda', 2 => 'terca', 3 => 'quarta', 4 => 'quinta',
        5 => 'sexta', 6 => 'sabado', 7 => 'domingo',
    ];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', OrdemServico::class);

        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->string('data_inicio'))->startOfDay()
            : now()->startOfMonth();
        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->string('data_fim'))->endOfDay()
            : now()->endOfDay();
        $unidadeId = $request->integer('unidade_id') ?: null;

        $osEntregues = OrdemServico::query()
            ->whereNotNull('entregue_em')
            ->whereBetween('entregue_em', [$dataInicio, $dataFim])
            ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId))
            ->get();

        $contas = ContaReceber::query()
            ->whereIn('ordem_servico_id', $osEntregues->pluck('id'))
            ->get();

        $faturamentoTotal = (string) $contas->sum(fn (ContaReceber $c) => (float) $c->valor_total);
        $ticketMedio = $contas->count() > 0 ? bcdiv($faturamentoTotal, (string) $contas->count(), 2) : '0.00';

        return Inertia::render('Painel/Indicadores/Index', [
            'filtros' => [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'unidade_id' => $unidadeId,
            ],
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
            'faturamentoTotal' => $faturamentoTotal,
            'ticketMedio' => $ticketMedio,
            'faturamentoPorServico' => $this->faturamentoPorServico($osEntregues->pluck('id')),
            'conversaoOrcamento' => $this->conversaoOrcamento($dataInicio, $dataFim, $unidadeId),
            'ocupacaoAgenda' => $this->ocupacaoAgenda($dataInicio, $dataFim, $unidadeId),
            'permanenciaMediaHoras' => $this->permanenciaMedia($osEntregues),
            'retornoClientes' => $this->retornoClientes($unidadeId),
        ]);
    }

    private function faturamentoPorServico($ordemServicoIds): array
    {
        return OrcamentoItem::query()
            ->whereIn('ordem_servico_id', $ordemServicoIds)
            ->where('status', OrcamentoItem::STATUS_APROVADO)
            ->get()
            ->groupBy('descricao')
            ->map(fn ($itens, $descricao) => [
                'descricao' => $descricao,
                'total' => (string) $itens->sum(fn (OrcamentoItem $i) => (float) $i->valor_unitario * $i->quantidade),
            ])
            ->sortByDesc(fn ($linha) => (float) $linha['total'])
            ->values()
            ->take(10)
            ->all();
    }

    private function conversaoOrcamento(Carbon $inicio, Carbon $fim, ?int $unidadeId): array
    {
        $query = OrdemServico::query()
            ->whereIn('status', OrdemServico::STATUS_POS_ORCAMENTO)
            ->whereBetween('created_at', [$inicio, $fim])
            ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId));

        $enviados = (clone $query)->count();
        $comAprovado = (clone $query)->whereHas('itens', fn ($q) => $q->where('status', OrcamentoItem::STATUS_APROVADO))->count();

        return [
            'enviados' => $enviados,
            'aprovados' => $comAprovado,
            'percentual' => $enviados > 0 ? round($comAprovado / $enviados * 100, 1) : 0,
        ];
    }

    private function ocupacaoAgenda(Carbon $inicio, Carbon $fim, ?int $unidadeId): array
    {
        $minutosOcupados = Agendamento::query()
            ->whereNotIn('status', Agendamento::STATUS_QUE_LIBERAM_HORARIO)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId))
            ->get()
            ->sum(fn (Agendamento $a) => $this->minutosEntre($a->hora_inicio, $a->hora_fim));

        $recursos = Recurso::query()
            ->where('ativo', true)
            ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId))
            ->get();

        $minutosDisponiveis = 0;
        for ($data = $inicio->copy()->startOfDay(); $data->lte($fim); $data->addDay()) {
            $diaChave = self::DIAS_SEMANA[$data->dayOfWeekIso];

            foreach ($recursos as $recurso) {
                foreach ($recurso->grade_semanal[$diaChave] ?? [] as $intervalo) {
                    $minutosDisponiveis += $this->minutosEntre($intervalo['inicio'], $intervalo['fim']);
                }
            }
        }

        return [
            'minutos_ocupados' => (int) $minutosOcupados,
            'minutos_disponiveis' => $minutosDisponiveis,
            'percentual' => $minutosDisponiveis > 0 ? round($minutosOcupados / $minutosDisponiveis * 100, 1) : 0,
        ];
    }

    private function minutosEntre(string $inicio, string $fim): int
    {
        [$hi, $mi] = explode(':', substr($inicio, 0, 5));
        [$hf, $mf] = explode(':', substr($fim, 0, 5));

        return (int) ($hf * 60 + $mf) - (int) ($hi * 60 + $mi);
    }

    private function permanenciaMedia($osEntregues): ?float
    {
        if ($osEntregues->isEmpty()) {
            return null;
        }

        $horas = $osEntregues->map(
            fn (OrdemServico $os) => $os->created_at->diffInMinutes($os->entregue_em) / 60,
        );

        return round($horas->avg(), 1);
    }

    /**
     * §16 — "retorno de clientes em 6 e 12 meses": % de clientes com OS
     * entregue na janela que tiveram mais de uma OS nesse período (voltaram).
     */
    private function retornoClientes(?int $unidadeId): array
    {
        $calcular = function (int $meses) use ($unidadeId) {
            $desde = now()->subMonths($meses);

            $porCliente = OrdemServico::query()
                ->whereNotNull('entregue_em')
                ->where('entregue_em', '>=', $desde)
                ->when($unidadeId, fn ($q) => $q->where('unidade_id', $unidadeId))
                ->get()
                ->groupBy('cliente_id');

            $totalClientes = $porCliente->count();
            $recorrentes = $porCliente->filter(fn ($grupo) => $grupo->count() > 1)->count();

            return [
                'total_clientes' => $totalClientes,
                'recorrentes' => $recorrentes,
                'percentual' => $totalClientes > 0 ? round($recorrentes / $totalClientes * 100, 1) : 0,
            ];
        };

        return [
            'seis_meses' => $calcular(6),
            'doze_meses' => $calcular(12),
        ];
    }
}
