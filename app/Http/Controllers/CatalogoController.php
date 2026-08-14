<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\JsonResponse;

/**
 * Catálogo global de marcas/modelos (§23), fonte: Tabela FIPE. Endpoints
 * simples de leitura para os selects/autocomplete do front — não é uma
 * página Inertia, só JSON. Ver docs/fipe.md.
 */
class CatalogoController extends Controller
{
    public function marcas(): JsonResponse
    {
        return response()->json(
            Marca::query()->where('tipo_veiculo', 'carro')->orderBy('nome')->get(['id', 'nome']),
        );
    }

    public function modelos(Marca $marca): JsonResponse
    {
        return response()->json(
            $marca->modelos()->orderBy('nome')->get(['id', 'nome']),
        );
    }
}
