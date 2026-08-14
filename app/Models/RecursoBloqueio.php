<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoBloqueio extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $fillable = [
        'empresa_id',
        'recurso_id',
        'data_inicio',
        'data_fim',
        'motivo',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }
}
