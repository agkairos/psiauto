<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaReceber extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'contas_receber';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'ordem_servico_id',
        'cliente_id',
        'forma_pagamento_id',
        'valor_total',
        'criado_por',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
    ];

    protected $appends = ['valor_recebido', 'status'];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }

    protected function valorRecebido(): Attribute
    {
        return Attribute::get(
            fn () => (string) $this->parcelas->sum(fn (Parcela $p) => (float) $p->valor_recebido),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::get(function () {
            $recebido = (float) $this->valor_recebido;
            $total = (float) $this->valor_total;

            if ($recebido >= $total) {
                return 'pago';
            }

            if ($recebido > 0) {
                return 'parcial';
            }

            $temParcelaVencida = $this->parcelas->contains(
                fn (Parcela $p) => $p->data_vencimento->isPast() && (float) $p->valor_recebido < (float) $p->valor,
            );

            return $temParcelaVencida ? 'atrasado' : 'pendente';
        });
    }
}
