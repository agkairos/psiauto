<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'email_contato',
        'telefone_contato',
        'logotipo_path',
        'segmentos',
        'slug',
        'descricao_publica',
        'situacao_assinatura',
        'aprovada_em',
    ];

    protected $casts = [
        'segmentos' => 'array',
        'aprovada_em' => 'datetime',
    ];

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidade::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
