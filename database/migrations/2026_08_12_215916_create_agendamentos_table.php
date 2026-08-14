<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();

            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos');

            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');

            // solicitado -> confirmado -> recebido -> em_execucao -> concluido
            // (ou cancelado / nao_compareceu a qualquer momento antes de concluído)
            $table->string('status', 20)->default('solicitado');

            // balcao = lançado pela empresa (único suportado por agora).
            // app fica reservado para quando existir o app do cliente (§19).
            $table->string('origem', 10)->default('balcao');

            $table->text('observacoes_cliente')->nullable();
            $table->string('motivo_cancelamento')->nullable();

            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'data']);
            $table->index(['recurso_id', 'data', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
