<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarBloqueioRequest;
use App\Models\Recurso;
use App\Models\RecursoBloqueio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BloqueiosController extends Controller
{
    public function store(SalvarBloqueioRequest $request, Recurso $recurso): RedirectResponse
    {
        Gate::authorize('update', $recurso);

        $recurso->bloqueios()->create([
            ...$request->validated(),
            'empresa_id' => Auth::user()->empresa_id,
        ]);

        return back()->with('sucesso', 'Bloqueio adicionado.');
    }

    public function destroy(RecursoBloqueio $bloqueio): RedirectResponse
    {
        Gate::authorize('update', $bloqueio->recurso);

        $bloqueio->delete();

        return back()->with('sucesso', 'Bloqueio removido.');
    }
}
