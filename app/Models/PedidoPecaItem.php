<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoPecaItem extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'pedido_peca_itens';

    public const DISPONIBILIDADE_EM_ESTOQUE = 'em_estoque';

    public const DISPONIBILIDADE_SOB_ENCOMENDA = 'sob_encomenda';

    public const DISPONIBILIDADE_INDISPONIVEL = 'indisponivel';

    protected $fillable = [
        'empresa_id',
        'pedido_peca_id',
        'produto_id',
        'descricao',
        'quantidade',
        'disponibilidade',
        'preco_unitario',
        'prazo_entrega_dias',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco_unitario' => 'decimal:2',
        'prazo_entrega_dias' => 'integer',
    ];

    public function pedidoPeca(): BelongsTo
    {
        return $this->belongsTo(PedidoPeca::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
