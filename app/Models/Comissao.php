<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comissao extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'comissoes';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_PAGA = 'paga';

    protected $fillable = [
        'empresa_id',
        'ordem_servico_id',
        'orcamento_item_id',
        'responsavel_id',
        'valor_base',
        'percentual',
        'valor_comissao',
        'status',
        'pago_em',
    ];

    protected $casts = [
        'valor_base' => 'decimal:2',
        'percentual' => 'decimal:2',
        'valor_comissao' => 'decimal:2',
        'pago_em' => 'datetime',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrcamentoItem::class, 'orcamento_item_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}
