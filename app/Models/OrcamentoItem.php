<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'orcamento_itens';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_APROVADO = 'aprovado';

    public const STATUS_RECUSADO = 'recusado';

    protected $fillable = [
        'empresa_id',
        'ordem_servico_id',
        'tipo',
        'servico_id',
        'produto_id',
        'responsavel_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'status',
        'aprovado_por',
        'aprovado_em',
        'motivo_recusa',
        'historico_precos',
        'criado_por',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'quantidade' => 'integer',
        'aprovado_em' => 'datetime',
        'historico_precos' => 'array',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function valorTotal(): string
    {
        return bcmul((string) $this->valor_unitario, (string) $this->quantidade, 2);
    }
}
