<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §12/§21 — consulta e orçamento de peças, reserva para retirada
     * presencial. Nunca há checkout online (CLAUDE.md); o pagamento é
     * sempre presencial na retirada.
     *
     * Decisões de implementação (spec silente nesses pontos):
     * - validade do orçamento: 7 dias corridos a partir de "orcado".
     * - prazo da reserva: 3 dias corridos a partir de "reservado".
     * - status por pedido inteiro (não por item), já que quem cria hoje é o
     *   funcionário em nome do cliente (sem app do cliente ainda).
     */
    public function up(): void
    {
        Schema::create('pedidos_peca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('veiculo_id')->nullable()->constrained('veiculos')->nullOnDelete();

            $table->string('status', 20)->default('solicitado');
            // solicitado | orcado | reservado | retirado | cancelado | expirado

            $table->date('validade_orcamento')->nullable();
            $table->date('reservado_ate')->nullable();
            $table->timestamp('retirado_em')->nullable();

            $table->text('observacoes')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_peca');
    }
};
