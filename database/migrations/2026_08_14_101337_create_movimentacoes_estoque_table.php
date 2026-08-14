<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §11 — "Movimentações registradas: entrada, saída, ajuste e perda"
     * (reserva fica de fora nesta fase — só existe hoje no fluxo avulso de
     * §12, ainda não implementado). Saldo do produto numa unidade = soma das
     * entradas/ajustes positivos menos saídas/perdas/ajustes negativos.
     */
    public function up(): void
    {
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos');

            $table->string('tipo', 10); // entrada | saida | ajuste | perda
            $table->integer('quantidade'); // sempre positivo; o tipo define o sentido
            $table->decimal('custo_unitario', 10, 2)->nullable(); // só em entrada, atualiza Produto.custo

            // Baixa automática "quando a peça é usada numa OS" (§11) — nossa
            // decisão de implementação: no momento em que o item de peça do
            // orçamento é aprovado. Ver docs/estoque.md.
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordens_servico')->nullOnDelete();

            $table->string('motivo')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'unidade_id', 'produto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};
