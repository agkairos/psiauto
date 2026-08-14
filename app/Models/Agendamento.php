<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agendamento extends Model
{
    use BelongsToEmpresa, HasFactory;

    public const STATUS_SOLICITADO = 'solicitado';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_RECEBIDO = 'recebido';

    public const STATUS_EM_EXECUCAO = 'em_execucao';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_NAO_COMPARECEU = 'nao_compareceu';

    // Status que não ocupam mais o horário do recurso (liberam a agenda).
    public const STATUS_QUE_LIBERAM_HORARIO = [self::STATUS_CANCELADO, self::STATUS_NAO_COMPARECEU];

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'recurso_id',
        'cliente_id',
        'veiculo_id',
        'servico_id',
        'data',
        'hora_inicio',
        'hora_fim',
        'status',
        'origem',
        'observacoes_cliente',
        'motivo_cancelamento',
        'criado_por',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function ordemServico(): HasOne
    {
        return $this->hasOne(OrdemServico::class);
    }
}
