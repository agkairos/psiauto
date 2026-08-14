<?php

namespace App\Http\Controllers\Painel;

use App\Actions\Financeiro\GerarContaReceberDaOS;
use App\Http\Controllers\Controller;
use App\Http\Requests\AbrirOrdemServicoRequest;
use App\Http\Requests\AtualizarOrdemServicoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrdensServicoController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', OrdemServico::class);

        $ordens = OrdemServico::query()
            ->with([
                'cliente:id,nome',
                'veiculo:id,placa,marca_id,modelo_id',
                'veiculo.marca:id,nome',
                'veiculo.modelo:id,nome',
                'unidade:id,nome',
                'itens' => fn ($q) => $q->orderBy('created_at'),
                'itens.servico:id,nome',
                'itens.aprovadoPor:id,name',
                'itens.responsavel:id,name',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Agendamentos recebidos ainda sem OS — atalho pra abrir a partir deles.
        $agendamentosRecebidos = Agendamento::query()
            ->where('status', Agendamento::STATUS_RECEBIDO)
            ->whereDoesntHave('ordemServico')
            ->whereDate('data', today())
            ->with(['cliente:id,nome', 'veiculo:id,placa'])
            ->get(['id', 'unidade_id', 'cliente_id', 'veiculo_id', 'hora_inicio']);

        return Inertia::render('Painel/OrdensServico/Index', [
            'ordens' => $ordens,
            'agendamentosRecebidos' => $agendamentosRecebidos,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
            'clientes' => Cliente::query()->where('ativo', true)->with('veiculos.marca:id,nome', 'veiculos.modelo:id,nome')->orderBy('nome')->get(['id', 'nome']),
            'servicos' => Servico::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'preco']),
            'formasPagamento' => FormaPagamento::query()->where('ativa', true)->orderBy('nome')->get(['id', 'nome']),
            'produtos' => Produto::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'preco_venda', 'unidade_medida']),
            'equipe' => User::query()->where('empresa_id', Auth::user()->empresa_id)->where('ativo', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(AbrirOrdemServicoRequest $request): RedirectResponse
    {
        OrdemServico::create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
            'status' => OrdemServico::STATUS_ABERTA,
            'aberta_por' => Auth::id(),
        ]);

        return back()->with('sucesso', 'OS aberta.');
    }

    public function update(AtualizarOrdemServicoRequest $request, OrdemServico $ordemServico): RedirectResponse
    {
        $ordemServico->update($request->validated());

        return back()->with('sucesso', 'OS atualizada.');
    }

    public function avancarStatus(Request $request, OrdemServico $ordemServico, GerarContaReceberDaOS $gerarContaReceber): RedirectResponse
    {
        Gate::authorize('update', $ordemServico);

        if (! in_array($ordemServico->status, OrdemServico::STATUS_POS_ORCAMENTO, true)
            || $ordemServico->status === OrdemServico::STATUS_ENTREGUE) {
            return back()->withErrors(['status' => 'Essa OS ainda não tem orçamento aprovado ou já foi entregue.']);
        }

        $empresaId = Auth::user()->empresa_id;

        $request->validate([
            'status' => ['required', Rule::in([...OrdemServico::STATUS_EXECUCAO_LIVRE, OrdemServico::STATUS_ENTREGUE])],
            'km_saida' => ['nullable', 'integer', 'min:0'],
            'forma_pagamento_id' => ['nullable', Rule::exists('formas_pagamento', 'id')->where('empresa_id', $empresaId)],
            'numero_parcelas' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $dados = ['status' => $request->string('status')->value()];

        if ($dados['status'] === OrdemServico::STATUS_ENTREGUE) {
            $dados['entregue_em'] = now();

            if ($request->filled('km_saida')) {
                $dados['km_saida'] = $request->integer('km_saida');
            }
        }

        $ordemServico->update($dados);

        // §13 — a conta a receber nasce sozinha quando a OS é entregue, com
        // base no que o cliente aprovou no orçamento.
        if ($dados['status'] === OrdemServico::STATUS_ENTREGUE && $ordemServico->contaReceber === null) {
            $gerarContaReceber(
                $ordemServico,
                $request->integer('forma_pagamento_id') ?: null,
                $request->integer('numero_parcelas') ?: 1,
            );
        }

        return back()->with('sucesso', 'Status da OS atualizado.');
    }
}
