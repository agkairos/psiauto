<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §11 — catálogo de produtos/peças da empresa. Saldo NÃO fica numa
     * coluna aqui — é sempre calculado a partir do razão de movimentações
     * (`movimentacoes_estoque`), por unidade. Custo é o último de entrada
     * (decisão de implementação — spec não define método de custeio).
     */
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('codigo')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->string('nome');
            $table->string('marca')->nullable(); // marca do produto (ex: Bosch) — não é a Marca de veículo
            $table->string('unidade_medida', 10)->default('un');

            $table->decimal('custo', 10, 2)->default(0);
            $table->decimal('preco_venda', 10, 2)->default(0);

            $table->unsignedInteger('estoque_minimo')->default(0);
            $table->boolean('visivel_para_cliente')->default(false);
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'ativo']);
            $table->unique(['empresa_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
