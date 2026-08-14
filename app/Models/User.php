<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'empresa_id', 'unidade_id', 'ativo',
    'google_id', 'convidado_por', 'convite_aceito_em',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    // IMPORTANTE: NÃO aplicar EmpresaScope/BelongsToEmpresa aqui. A resolução
    // do usuário autenticado (Auth::user(), via SessionGuard::retrieveById)
    // consulta este model — e EmpresaScope::apply() chama Auth::user() para
    // decidir o filtro. As duas coisas juntas criam recursão infinita
    // (estouro de memória) na primeira request de cada sessão. Isolamento
    // multiempresa para User é feito explicitamente nas queries dos
    // controllers (ver Painel\UsuariosController) + UserPolicy — nunca via
    // scope global neste model específico.
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'convite_aceito_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function convidadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'convidado_por');
    }

    public function convitePendente(): bool
    {
        return $this->convite_aceito_em === null && $this->google_id === null;
    }
}
