<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcela extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $fillable = [
        'empresa_id',
        'conta_receber_id',
        'numero',
        'valor',
        'data_vencimento',
        'valor_recebido',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_recebido' => 'decimal:2',
        'data_vencimento' => 'date',
    ];

    protected $appends = ['status'];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class);
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(Recebimento::class);
    }

    protected function status(): Attribute
    {
        return Attribute::get(function () {
            $recebido = (float) $this->valor_recebido;
            $valor = (float) $this->valor;

            if ($recebido >= $valor) {
                return 'pago';
            }

            if ($recebido > 0) {
                return 'parcial';
            }

            return $this->data_vencimento->isPast() ? 'atrasado' : 'pendente';
        });
    }
}
