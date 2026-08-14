<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaPagar extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'contas_pagar';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'fornecedor',
        'descricao',
        'categoria',
        'valor',
        'data_vencimento',
        'recorrente',
        'periodicidade',
        'criado_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
        'recorrente' => 'boolean',
    ];

    protected $appends = ['valor_pago', 'status'];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoContaPagar::class);
    }

    protected function valorPago(): Attribute
    {
        return Attribute::get(
            fn () => (string) $this->pagamentos->sum(fn (PagamentoContaPagar $p) => (float) $p->valor),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::get(function () {
            $pago = (float) $this->valor_pago;
            $total = (float) $this->valor;

            if ($pago >= $total) {
                return 'paga';
            }

            if ($pago > 0) {
                return 'parcial';
            }

            return $this->data_vencimento->isPast() ? 'atrasada' : 'pendente';
        });
    }
}
