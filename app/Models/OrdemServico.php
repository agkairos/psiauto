<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdemServico extends Model
{
    use BelongsToEmpresa, HasFactory, SoftDeletes;

    protected $table = 'ordens_servico';

    // §07 — do recebimento à entrega.
    public const STATUS_ABERTA = 'aberta';

    public const STATUS_AGUARDANDO_APROVACAO = 'aguardando_aprovacao';

    public const STATUS_EM_EXECUCAO = 'em_execucao';

    public const STATUS_AGUARDANDO_PECA = 'aguardando_peca';

    public const STATUS_EM_TESTE = 'em_teste';

    public const STATUS_PRONTO = 'pronto';

    public const STATUS_ENTREGUE = 'entregue';

    // Depois que o orçamento é enviado (tem pelo menos 1 item), a OS não
    // volta a ser editada como se ainda estivesse em diagnóstico puro.
    public const STATUS_POS_ORCAMENTO = [
        self::STATUS_AGUARDANDO_APROVACAO, self::STATUS_EM_EXECUCAO, self::STATUS_AGUARDANDO_PECA,
        self::STATUS_EM_TESTE, self::STATUS_PRONTO, self::STATUS_ENTREGUE,
    ];

    // Ordem livre entre esses três — a equipe pode ir e voltar (ex.: de
    // "em teste" pra "aguardando peça" se algo não funcionar).
    public const STATUS_EXECUCAO_LIVRE = [
        self::STATUS_EM_EXECUCAO, self::STATUS_AGUARDANDO_PECA, self::STATUS_EM_TESTE, self::STATUS_PRONTO,
    ];

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'agendamento_id',
        'cliente_id',
        'veiculo_id',
        'status',
        'checklist_entrada',
        'reclamacao_cliente',
        'diagnostico_tecnico',
        'km_saida',
        'entregue_em',
        'aberta_por',
    ];

    protected $casts = [
        'checklist_entrada' => 'array',
        'km_saida' => 'integer',
        'entregue_em' => 'datetime',
    ];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function abertaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aberta_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class, 'ordem_servico_id');
    }

    public function contaReceber(): HasOne
    {
        return $this->hasOne(ContaReceber::class, 'ordem_servico_id');
    }

    /**
     * Soma dos itens aprovados — base do valor cobrado do cliente (§13).
     */
    public function valorAprovado(): string
    {
        return (string) $this->itens
            ->where('status', OrcamentoItem::STATUS_APROVADO)
            ->sum(fn (OrcamentoItem $item) => (float) $item->valor_unitario * $item->quantidade);
    }
}
