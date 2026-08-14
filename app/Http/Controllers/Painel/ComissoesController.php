<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComissoesController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Comissao::class);

        $comissoes = Comissao::query()
            ->with(['responsavel:id,name', 'item:id,descricao', 'ordemServico:id'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Painel/Comissoes/Index', [
            'comissoes' => $comissoes,
        ]);
    }

    public function marcarPaga(Comissao $comissao): RedirectResponse
    {
        Gate::authorize('update', $comissao);

        $comissao->update([
            'status' => Comissao::STATUS_PAGA,
            'pago_em' => now(),
        ]);

        return back()->with('sucesso', 'Comissão marcada como paga.');
    }
}
