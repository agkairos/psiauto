<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoAplicacao extends Model
{
    use HasFactory;

    protected $table = 'produto_aplicacoes';

    protected $fillable = [
        'produto_id',
        'marca_id',
        'modelo_id',
        'ano_inicio',
        'ano_fim',
    ];

    protected $casts = [
        'ano_inicio' => 'integer',
        'ano_fim' => 'integer',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }
}
