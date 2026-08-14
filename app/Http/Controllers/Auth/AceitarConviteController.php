<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AceitarConviteRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AceitarConviteController extends Controller
{
    public function show(Request $request, User $usuario): Response|RedirectResponse
    {
        if (! $usuario->convitePendente()) {
            return to_route('login')->withErrors([
                'email' => 'Esse convite já foi usado. Faça login normalmente.',
            ]);
        }

        return Inertia::render('Auth/AceitarConvite', [
            'nome' => $usuario->name,
            'email' => $usuario->email,
            // O form posta pra essa mesma URL assinada — é isso que valida o
            // convite também no POST, não só no GET.
            'url' => $request->fullUrl(),
        ]);
    }

    public function store(AceitarConviteRequest $request, User $usuario): RedirectResponse
    {
        if (! $usuario->convitePendente()) {
            return to_route('login')->withErrors([
                'email' => 'Esse convite já foi usado. Faça login normalmente.',
            ]);
        }

        $usuario->forceFill([
            'password' => Hash::make($request->validated('password')),
            'convite_aceito_em' => now(),
        ])->save();

        Auth::login($usuario);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
