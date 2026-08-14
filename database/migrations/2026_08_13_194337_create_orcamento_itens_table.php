<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §08 — orçamento item a item, aprovado individualmente. Sem módulo de
     * peças/estoque (§11) ainda, então "peça" aqui é descrição livre + preço,
     * não um produto de catálogo — ligar depois quando §11 existir.
     */
    public function up(): void
    {
        Schema::create('orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();

            $table->string('tipo', 10); // servico | peca
            $table->foreignId('servico_id')->nullable()->constrained('servicos');
            $table->string('descricao');

            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('valor_unitario', 10, 2);

            $table->string('status', 10)->default('pendente'); // pendente | aprovado | recusado
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable();
            $table->string('motivo_recusa')->nullable();

            // Auditoria de alteração de preço (autor, data, valor anterior) —
            // regra explícita do CLAUDE.md. Formato:
            // [{"valor_anterior": "100.00", "alterado_por": 3, "alterado_em": "..."}]
            $table->jsonb('historico_precos')->nullable();

            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['ordem_servico_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
    }
};
