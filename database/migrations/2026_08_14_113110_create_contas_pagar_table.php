<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §13 — "fornecedores, despesas fixas e despesas recorrentes, com
     * alerta de vencimento" + "centro de custo e classificação de receitas
     * e despesas" (categoria aqui é texto livre — spec não define taxonomia
     * fixa). Recorrência: ao quitar uma conta `recorrente`, gera a próxima
     * ocorrência automaticamente (decisão de implementação, spec não
     * detalha o mecanismo — ver docs/financeiro.md).
     */
    public function up(): void
    {
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->nullable()->constrained('unidades')->nullOnDelete();

            $table->string('fornecedor')->nullable();
            $table->string('descricao');
            $table->string('categoria')->nullable(); // centro de custo, texto livre
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');

            $table->boolean('recorrente')->default(false);
            $table->string('periodicidade', 10)->nullable(); // mensal (única suportada por ora)

            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
    }
};
