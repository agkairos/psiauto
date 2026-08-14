<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de cada baixa (total ou parcial) contra uma parcela — histórico de
     * auditoria de recebimento, já que uma parcela pode ser paga aos poucos.
     */
    public function up(): void
    {
        Schema::create('recebimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->foreignId('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->nullOnDelete();

            $table->decimal('valor', 10, 2);
            $table->date('data');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['empresa_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recebimentos');
    }
};
