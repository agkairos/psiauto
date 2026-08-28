<?php

namespace App\Http\Controllers\Painel;

use App\Events\AgendamentoStatusAlterado;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Recurso;
use App\Models\Servico;
use App\Models\Unidade;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgendamentosController extends Controller
{
    private const STATUS_VALIDOS = [
        Agendamento::STATUS_SOLICITADO, Agendamento::STATUS_CONFIRMADO, Agendamento::STATUS_RECEBIDO,
        Agendamento::STATUS_EM_EXECUCAO, Agendamento::STATUS_CONCLUIDO, Agendamento::STATUS_NAO_COMPARECEU,
    ];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Agendamento::class);

        $data = $request->filled('data') ? Carbon::parse($request->string('data')) : today();

        $unidadeId = $request->integer('unidade_id') ?: $this->unidadePadrao();

        $recursos = Recurso::query()
            ->where('unidade_id', $unidadeId)
            ->where('ativo', true)
            ->with('servicos:id,nome')
            ->orderBy('nome')
            ->get();

        $agendamentos = Agendamento::query()
            ->where('unidade_id', $unidadeId)
            ->whereDate('data', $data)
            ->with(['cliente:id,nome', 'veiculo:id,placa,marca_id,modelo_id', 'veiculo.marca:id,nome', 'veiculo.modelo:id,nome', 'servico:id,nome,tempo_execucao_minutos'])
            ->get();

        return Inertia::render('Painel/Agendamentos/Index', [
            'data' => $data->toDateString(),
            'unidadeId' => $unidadeId,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
            'recursos' => $recursos,
            'agendamentos' => $agendamentos,
            'servicos' => Servico::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tempo_execucao_minutos']),
        ]);
    }

    /**
     * Prioriza a primeira unidade que já tem recurso ativo — evita abrir a
     * tela numa unidade vazia só porque o nome dela vem antes no alfabeto
     * ou porque foi cadastrada primeiro sem ainda ter agenda configurada.
     */
    private function unidadePadrao(): ?int
    {
        $comRecurso = Unidade::query()
            ->whereHas('recursos', fn ($q) => $q->where('ativo', true))
            ->orderBy('id')
            ->value('id');

        return $comRecurso ?? Unidade::query()->orderBy('id')->value('id');
    }

    public function store(SalvarAgendamentoRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $recurso = Recurso::findOrFail($dados['recurso_id']);
        $servico = Servico::findOrFail($dados['servico_id']);

        $agendamento = Agendamento::create([
            ...$dados,
            'empresa_id' => Auth::user()->empresa_id,
            'unidade_id' => $recurso->unidade_id,
            'hora_fim' => $request->calcularHoraFim($dados['hora_inicio'], $servico->tempo_execucao_minutos),
            'status' => Agendamento::STATUS_SOLICITADO,
            'criado_por' => Auth::id(),
        ]);

        broadcast(new AgendamentoStatusAlterado($agendamento));

        return back()->with('sucesso', 'Agendamento criado.');
    }

    public function update(SalvarAgendamentoRequest $request, Agendamento $agendamento): RedirectResponse
    {
        $dados = $request->validated();
        $recurso = Recurso::findOrFail($dados['recurso_id']);
        $servico = Servico::findOrFail($dados['servico_id']);

        $agendamento->update([
            ...$dados,
            'unidade_id' => $recurso->unidade_id,
            'hora_fim' => $request->calcularHoraFim($dados['hora_inicio'], $servico->tempo_execucao_minutos),
        ]);

        broadcast(new AgendamentoStatusAlterado($agendamento));

        return back()->with('sucesso', 'Agendamento remarcado.');
    }

    public function atualizarStatus(Request $request, Agendamento $agendamento): RedirectResponse
    {
        Gate::authorize('update', $agendamento);

        $request->validate([
            'status' => ['required', Rule::in(self::STATUS_VALIDOS)],
        ]);

        $agendamento->update(['status' => $request->string('status')->value()]);

        broadcast(new AgendamentoStatusAlterado($agendamento));

        return back()->with('sucesso', 'Status atualizado.');
    }

    public function cancelar(Request $request, Agendamento $agendamento): RedirectResponse
    {
        Gate::authorize('update', $agendamento);

        $request->validate([
            'motivo_cancelamento' => ['nullable', 'string', 'max:255'],
        ]);

        $agendamento->update([
            'status' => Agendamento::STATUS_CANCELADO,
            'motivo_cancelamento' => $request->string('motivo_cancelamento')->value() ?: null,
        ]);

        broadcast(new AgendamentoStatusAlterado($agendamento));

        return back()->with('sucesso', 'Agendamento cancelado.');
    }
}
