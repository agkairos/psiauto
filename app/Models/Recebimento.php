<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recebimento extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $fillable = [
        'empresa_id',
        'parcela_id',
        'forma_pagamento_id',
        'valor',
        'data',
        'registrado_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data' => 'date',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
