<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veiculo extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'marca_id',
        'modelo_id',
        'placa',
        'chassi',
        'ano_fabricacao',
        'ano_modelo',
        'versao',
        'cor',
        'quilometragem_atual',
        'observacoes_internas',
    ];

    protected $casts = [
        'ano_fabricacao' => 'integer',
        'ano_modelo' => 'integer',
        'quilometragem_atual' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
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
