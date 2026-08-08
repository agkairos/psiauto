<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unidade extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'nome',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'latitude',
        'longitude',
        'horario_funcionamento',
        'ativa',
    ];

    protected $casts = [
        'horario_funcionamento' => 'array',
        'ativa' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
