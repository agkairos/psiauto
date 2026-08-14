<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recurso extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'nome',
        'grade_semanal',
        'ativo',
    ];

    protected $casts = [
        'grade_semanal' => 'array',
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'recurso_servico');
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(RecursoBloqueio::class);
    }
}
