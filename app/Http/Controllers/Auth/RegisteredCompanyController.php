<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrarEmpresaRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Support\SlugUnico;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredCompanyController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegistrarEmpresaRequest $request)
    {
        $dados = $request->validated();

        $user = DB::transaction(function () use ($dados) {
            $empresa = Empresa::create([
                'razao_social' => $dados['razao_social'],
                'nome_fantasia' => $dados['nome_fantasia'],
                'cnpj' => $dados['cnpj'],
                'segmentos' => $dados['segmentos'],
                'slug' => SlugUnico::paraEmpresa($dados['nome_fantasia']),
            ]);

            $user = User::create([
                'empresa_id' => $empresa->id,
                'name' => $dados['name'],
                'email' => $dados['email'],
                'password' => Hash::make($dados['password']),
                'convite_aceito_em' => now(),
            ]);

            $user->assignRole('proprietario');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
