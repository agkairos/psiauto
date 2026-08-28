<?php

namespace App\Http\Controllers\Painel;

use App\Actions\PedidosPeca\EstornarReservaDoPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrecificarPedidoPecaItemRequest;
use App\Http\Requests\SalvarPedidoPecaRequest;
use App\Models\PedidoPeca;
use App\Models\PedidoPecaItem;
use App\Models\Produto;
use App\Models\Unidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PedidosPecaController extends Controller
{
    // §12 — validade do orçamento e prazo da reserva não são definidos pela
    // especificação; decisão de implementação documentada na migration.
    private const DIAS_VALIDADE_ORCAMENTO = 7;

    private const DIAS_RESERVA = 3;

    public function index(): Response
    {
        Gate::authorize('viewAny', PedidoPeca::class);

        $pedidos = PedidoPeca::query()
            ->with(['cliente:id,nome', 'veiculo:id,placa', 'unidade:id,nome', 'itens.produto:id,nome'])
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Painel/PedidosPeca/Index', [
            'pedidos' => $pedidos,
            'unidades' => Unidade::query()->orderBy('nome')->get(['id', 'nome']),
            'produtos' => Produto::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(SalvarPedidoPecaRequest $request): RedirectResponse
    {
        Gate::authorize('create', PedidoPeca::class);

        $dados = $request->validated();

        DB::transaction(function () use ($dados) {
            $pedido = PedidoPeca::create([
                'empresa_id' => Auth::user()->empresa_id,
                'unidade_id' => $dados['unidade_id'],
                'cliente_id' => $dados['cliente_id'],
                'veiculo_id' => $dados['veiculo_id'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
                'status' => PedidoPeca::STATUS_SOLICITADO,
                'criado_por' => Auth::id(),
            ]);

            foreach ($dados['itens'] as $item) {
                $pedido->itens()->create([
                    'empresa_id' => $pedido->empresa_id,
                    'produto_id' => $item['produto_id'] ?? null,
                    'descricao' => $item['descricao'],
                    'quantidade' => $item['quantidade'],
                ]);
            }
        });

        return back()->with('sucesso', 'Pedido de peça registrado.');
    }

    public function precificarItem(PrecificarPedidoPecaItemRequest $request, PedidoPecaItem $item): RedirectResponse
    {
        $pedido = $item->pedidoPeca;

        if ($pedido->status !== PedidoPeca::STATUS_SOLICITADO && $pedido->status !== PedidoPeca::STATUS_ORCADO) {
            return back()->withErrors(['item' => 'Só dá pra precificar item de pedido ainda em orçamento.']);
        }

        $dados = $request->validated();
        $item->update([
            'disponibilidade' => $dados['disponibilidade'],
            'preco_unitario' => $dados['disponibilidade'] === PedidoPecaItem::DISPONIBILIDADE_INDISPONIVEL ? null : $dados['preco_unitario'],
            'prazo_entrega_dias' => $dados['prazo_entrega_dias'] ?? null,
        ]);

        // §12 — assim que todo item tiver disponibilidade definida, o
        // orçamento fica pronto e passa a valer por um prazo.
        $faltaPrecificar = $pedido->itens()->whereNull('disponibilidade')->exists();
        if (! $faltaPrecificar && $pedido->status === PedidoPeca::STATUS_SOLICITADO) {
            $pedido->update([
                'status' => PedidoPeca::STATUS_ORCADO,
                'validade_orcamento' => now()->addDays(self::DIAS_VALIDADE_ORCAMENTO),
            ]);
            // TODO: aviso automático por e-mail (§12) — job assíncrono, ver skill realtime-status para o padrão de disparo assíncrono.
        }

        return back()->with('sucesso', 'Item precificado.');
    }

    public function reservar(PedidoPeca $pedidoPeca): RedirectResponse
    {
        Gate::authorize('update', $pedidoPeca);

        if ($pedidoPeca->status !== PedidoPeca::STATUS_ORCADO) {
            return back()->withErrors(['pedido' => 'Só dá pra reservar um pedido já orçado.']);
        }

        $itensEmEstoque = $pedidoPeca->itens()
            ->where('disponibilidade', PedidoPecaItem::DISPONIBILIDADE_EM_ESTOQUE)
            ->whereNotNull('produto_id')
            ->with('produto')
            ->get();

        foreach ($itensEmEstoque as $item) {
            $saldo = $item->produto->saldoNaUnidade($pedidoPeca->unidade_id);
            if ($saldo < $item->quantidade) {
                return back()->withErrors(['pedido' => "Saldo insuficiente para \"{$item->descricao}\" (atual: {$saldo})."]);
            }
        }

        DB::transaction(function () use ($pedidoPeca, $itensEmEstoque) {
            foreach ($itensEmEstoque as $item) {
                $item->produto->movimentacoes()->create([
                    'empresa_id' => $pedidoPeca->empresa_id,
                    'unidade_id' => $pedidoPeca->unidade_id,
                    'tipo' => Produto::TIPO_RESERVA,
                    'quantidade' => $item->quantidade,
                    'motivo' => "Reserva - pedido de peça #{$pedidoPeca->id}",
                    'criado_por' => Auth::id(),
                ]);
            }

            $pedidoPeca->update([
                'status' => PedidoPeca::STATUS_RESERVADO,
                'reservado_ate' => now()->addDays(self::DIAS_RESERVA),
            ]);
        });

        return back()->with('sucesso', 'Peças reservadas para retirada.');
    }

    public function retirar(PedidoPeca $pedidoPeca): RedirectResponse
    {
        Gate::authorize('update', $pedidoPeca);

        if ($pedidoPeca->status !== PedidoPeca::STATUS_RESERVADO) {
            return back()->withErrors(['pedido' => 'Só dá pra dar baixa num pedido reservado.']);
        }

        $pedidoPeca->update([
            'status' => PedidoPeca::STATUS_RETIRADO,
            'retirado_em' => now(),
        ]);

        return back()->with('sucesso', 'Retirada confirmada.');
    }

    public function cancelar(PedidoPeca $pedidoPeca, EstornarReservaDoPedido $estornarReserva): RedirectResponse
    {
        Gate::authorize('update', $pedidoPeca);

        if (in_array($pedidoPeca->status, [PedidoPeca::STATUS_RETIRADO, PedidoPeca::STATUS_CANCELADO], true)) {
            return back()->withErrors(['pedido' => 'Esse pedido já foi encerrado.']);
        }

        DB::transaction(function () use ($pedidoPeca, $estornarReserva) {
            $estornarReserva($pedidoPeca, Auth::id());
            $pedidoPeca->update(['status' => PedidoPeca::STATUS_CANCELADO]);
        });

        return back()->with('sucesso', 'Pedido cancelado.');
    }
}
