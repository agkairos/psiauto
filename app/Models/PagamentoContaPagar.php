<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoContaPagar extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'pagamentos_conta_pagar';

    protected $fillable = [
        'empresa_id',
        'conta_pagar_id',
        'valor',
        'data',
        'registrado_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data' => 'date',
    ];

    public function contaPagar(): BelongsTo
    {
        return $this->belongsTo(ContaPagar::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
