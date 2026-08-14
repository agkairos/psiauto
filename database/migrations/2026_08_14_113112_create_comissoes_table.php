<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §13 — "comissões calculadas por serviço ou por peça, com relatório
     * por funcionário e controle de pagamento". Entidade própria (não
     * reaproveita contas_pagar) — controle de pagamento é campo simples
     * pendente/paga aqui, decisão de implementação (spec não define se
     * comissão deveria compor o mesmo fluxo de contas a pagar).
     */
    public function up(): void
    {
        Schema::create('comissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->foreignId('orcamento_item_id')->constrained('orcamento_itens')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->constrained('users');

            $table->decimal('valor_base', 10, 2); // valor_unitario * quantidade do item
            $table->decimal('percentual', 5, 2);
            $table->decimal('valor_comissao', 10, 2);

            $table->string('status', 10)->default('pendente'); // pendente | paga
            $table->timestamp('pago_em')->nullable();

            $table->timestamps();

            $table->index(['empresa_id', 'responsavel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissoes');
    }
};
