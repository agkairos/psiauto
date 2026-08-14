<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SAIDA = 'saida';

    public const TIPO_AJUSTE = 'ajuste';

    public const TIPO_PERDA = 'perda';

    // §12 — reserva para retirada presencial: prende o estoque sem ser uma
    // saída definitiva ainda; "estorno" libera de volta se a reserva expira
    // ou é cancelada antes da retirada.
    public const TIPO_RESERVA = 'reserva';

    public const TIPO_ESTORNO = 'estorno';

    // Sinal de cada tipo de movimentação na soma do saldo.
    public const SINAL_POR_TIPO = [
        self::TIPO_ENTRADA => 1,
        self::TIPO_SAIDA => -1,
        self::TIPO_AJUSTE => 1, // ajuste negativo se registra com quantidade negativa direto no controller
        self::TIPO_PERDA => -1,
        self::TIPO_RESERVA => -1,
        self::TIPO_ESTORNO => 1,
    ];

    protected $fillable = [
        'empresa_id',
        'codigo',
        'codigo_barras',
        'nome',
        'marca',
        'unidade_medida',
        'custo',
        'preco_venda',
        'comissao_percentual',
        'estoque_minimo',
        'visivel_para_cliente',
        'ativo',
    ];

    protected $casts = [
        'custo' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'comissao_percentual' => 'decimal:2',
        'estoque_minimo' => 'integer',
        'visivel_para_cliente' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class);
    }

    public function aplicacoes(): HasMany
    {
        return $this->hasMany(ProdutoAplicacao::class);
    }

    public function saldoNaUnidade(int $unidadeId): int
    {
        return $this->movimentacoes()
            ->where('unidade_id', $unidadeId)
            ->get()
            ->sum(fn (MovimentacaoEstoque $m) => $m->quantidade * self::SINAL_POR_TIPO[$m->tipo]);
    }
}
