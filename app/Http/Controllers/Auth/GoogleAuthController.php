<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompletarCadastroGoogleRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Support\SlugUnico;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

/**
 * Ver docs/login-social.md para o desenho completo do fluxo.
 *
 * Regra central: login via Google só autentica quem já existe (por google_id
 * ou por e-mail já cadastrado/convidado). E-mail sem correspondência nunca
 * cria empresa/usuário aqui — cai no fluxo de completar cadastro de empresa
 * (o Google só é usado como atalho de proprietário se cadastrando).
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $usuario = User::where('google_id', $googleUser->getId())->first();

        if ($usuario === null) {
            $usuario = User::where('email', $googleUser->getEmail())->first();

            if ($usuario !== null) {
                $usuario->forceFill([
                    'google_id' => $googleUser->getId(),
                    'convite_aceito_em' => $usuario->convite_aceito_em ?? now(),
                ])->save();
            }
        }

        if ($usuario !== null) {
            if (! $usuario->ativo) {
                return to_route('login')->withErrors([
                    'email' => 'Esse usuário está inativo. Fale com o proprietário da empresa.',
                ]);
            }

            Auth::login($usuario);
            $request->session()->regenerate();

            return to_route('dashboard');
        }

        // Nenhum usuário encontrado: trata como início de cadastro de empresa
        // pelo proprietário. Guarda o perfil do Google na sessão só até o
        // formulário de completar cadastro ser enviado.
        $request->session()->put('google_signup', [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ]);

        return to_route('registrar.completar');
    }

    public function completar(): Response|RedirectResponse
    {
        $dados = request()->session()->get('google_signup');

        if ($dados === null) {
            return to_route('registrar');
        }

        return Inertia::render('Auth/CompletarCadastroGoogle', [
            'nome' => $dados['name'],
            'email' => $dados['email'],
        ]);
    }

    public function completarStore(CompletarCadastroGoogleRequest $request): RedirectResponse
    {
        $dadosGoogle = $request->session()->get('google_signup');

        if ($dadosGoogle === null) {
            return to_route('registrar');
        }

        $dados = $request->validated();

        $usuario = DB::transaction(function () use ($dados, $dadosGoogle) {
            $empresa = Empresa::create([
                'razao_social' => $dados['razao_social'],
                'nome_fantasia' => $dados['nome_fantasia'],
                'cnpj' => $dados['cnpj'],
                'segmentos' => $dados['segmentos'],
                'slug' => SlugUnico::paraEmpresa($dados['nome_fantasia']),
            ]);

            $usuario = User::create([
                'empresa_id' => $empresa->id,
                'name' => $dadosGoogle['name'],
                'email' => $dadosGoogle['email'],
                'google_id' => $dadosGoogle['google_id'],
                'password' => null,
                'convite_aceito_em' => now(),
            ]);

            $usuario->assignRole('proprietario');

            return $usuario;
        });

        $request->session()->forget('google_signup');

        event(new Registered($usuario));

        Auth::login($usuario);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
