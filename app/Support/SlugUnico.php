<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Support\Str;

class SlugUnico
{
    public static function paraEmpresa(string $nomeFantasia): string
    {
        $base = Str::slug($nomeFantasia);
        $slug = $base;
        $sufixo = 1;

        while (Empresa::withTrashed()->where('slug', $slug)->exists()) {
            $sufixo++;
            $slug = "{$base}-{$sufixo}";
        }

        return $slug;
    }
}
