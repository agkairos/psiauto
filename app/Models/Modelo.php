<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modelo extends Model
{
    use HasFactory;

    protected $fillable = [
        'marca_id',
        'nome',
        'fipe_codigo',
    ];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }
}
