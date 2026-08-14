<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPagamento extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'formas_pagamento';

    protected $fillable = [
        'empresa_id',
        'nome',
        'taxa_percentual',
        'prazo_recebimento_dias',
        'ativa',
    ];

    protected $casts = [
        'taxa_percentual' => 'decimal:2',
        'prazo_recebimento_dias' => 'integer',
        'ativa' => 'boolean',
    ];
}
