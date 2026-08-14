<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger de baixas (total ou parcial) contra uma conta a pagar — mesmo
     * padrão de `recebimentos` do lado das contas a receber.
     */
    public function up(): void
    {
        Schema::create('pagamentos_conta_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('conta_pagar_id')->constrained('contas_pagar')->cascadeOnDelete();

            $table->decimal('valor', 10, 2);
            $table->date('data');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_conta_pagar');
    }
};
