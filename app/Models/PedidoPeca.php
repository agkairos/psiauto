<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoPeca extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'pedidos_peca';

    public const STATUS_SOLICITADO = 'solicitado';

    public const STATUS_ORCADO = 'orcado';

    public const STATUS_RESERVADO = 'reservado';

    public const STATUS_RETIRADO = 'retirado';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_EXPIRADO = 'expirado';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'cliente_id',
        'veiculo_id',
        'status',
        'validade_orcamento',
        'reservado_ate',
        'retirado_em',
        'observacoes',
        'criado_por',
    ];

    protected $casts = [
        'validade_orcamento' => 'date',
        'reservado_ate' => 'date',
        'retirado_em' => 'datetime',
    ];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoPecaItem::class);
    }
}
