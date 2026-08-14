<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servico extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'segmento',
        'tipo_preco',
        'preco',
        'tempo_execucao_minutos',
        'garantia_dias',
        'garantia_km',
        'comissao_percentual',
        'custo',
        'ativo',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'comissao_percentual' => 'decimal:2',
        'custo' => 'decimal:2',
        'tempo_execucao_minutos' => 'integer',
        'garantia_dias' => 'integer',
        'garantia_km' => 'integer',
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
