<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Requests\AtualizarUsuarioRequest;
use App\Http\Requests\ConvidarUsuarioRequest;
use App\Models\Unidade;
use App\Models\User;
use App\Notifications\ConviteUsuarioNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UsuariosController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $usuarios = User::query()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->with(['unidade:id,nome', 'roles:name'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Painel/Usuarios/Index', [
            'usuarios' => $usuarios,
            'unidades' => Unidade::query()->select('id', 'nome')->orderBy('nome')->get(),
        ]);
    }

    public function store(ConvidarUsuarioRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $usuario = User::create([
            'empresa_id' => Auth::user()->empresa_id,
            'name' => $dados['name'],
            'email' => $dados['email'],
            'unidade_id' => $dados['unidade_id'] ?? null,
            'convidado_por' => Auth::id(),
            'password' => null,
        ]);

        $usuario->assignRole($dados['role']);
        $usuario->notify(new ConviteUsuarioNotification(Auth::user()));

        return back()->with('sucesso', 'Convite enviado para '.$usuario->email.'.');
    }

    public function update(AtualizarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $dados = $request->validated();

        $usuario->update(['unidade_id' => $dados['unidade_id'] ?? null]);
        $usuario->syncRoles([$dados['role']]);

        return back()->with('sucesso', 'Usuário atualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        Gate::authorize('delete', $usuario);

        $usuario->update(['ativo' => false]);

        return back()->with('sucesso', 'Usuário desativado.');
    }

    public function reativar(User $usuario): RedirectResponse
    {
        Gate::authorize('update', $usuario);

        $usuario->update(['ativo' => true]);

        return back()->with('sucesso', 'Usuário reativado.');
    }
}
