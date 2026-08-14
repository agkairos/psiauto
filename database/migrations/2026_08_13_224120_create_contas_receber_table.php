<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §13 — "gerada automaticamente pela ordem de serviço [...]". Decisão de
     * implementação (spec não define o gatilho exato): nasce quando a OS
     * vira "entregue" — ver docs/financeiro.md.
     */
    public function up(): void
    {
        Schema::create('contas_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();

            // Uma conta a receber por OS (venda de peça avulsa fora de uma
            // OS ainda não existe — §11 não foi implementado).
            $table->foreignId('ordem_servico_id')->unique()->constrained('ordens_servico');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->nullOnDelete();

            $table->decimal('valor_total', 10, 2);

            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'unidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_receber');
    }
};
