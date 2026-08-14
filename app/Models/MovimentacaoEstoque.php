<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'produto_id',
        'tipo',
        'quantidade',
        'custo_unitario',
        'ordem_servico_id',
        'motivo',
        'criado_por',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'custo_unitario' => 'decimal:2',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
