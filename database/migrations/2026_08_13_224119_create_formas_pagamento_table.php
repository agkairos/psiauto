<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §13 — "formas de pagamento configuráveis, com taxa e prazo de
     * recebimento de cartão". Por empresa (cada empresa negocia sua própria
     * taxa com a maquininha/banco).
     */
    public function up(): void
    {
        Schema::create('formas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome'); // Dinheiro, Pix, Cartão de crédito, ...
            $table->decimal('taxa_percentual', 5, 2)->default(0);
            $table->unsignedInteger('prazo_recebimento_dias')->default(0);
            $table->boolean('ativa')->default(true);

            $table->timestamps();

            $table->index(['empresa_id', 'ativa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formas_pagamento');
    }
};
