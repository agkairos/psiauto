<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §11 — "em quais marcas, modelos e anos a peça serve". Usa o catálogo
     * global de marcas/modelos da FIPE (ver docs/fipe.md). modelo_id nulo =
     * serve pra marca inteira; ano_inicio/ano_fim nulos = todos os anos.
     */
    public function up(): void
    {
        Schema::create('produto_aplicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('marca_id')->constrained('marcas');
            $table->foreignId('modelo_id')->nullable()->constrained('modelos')->cascadeOnDelete();

            $table->unsignedSmallInteger('ano_inicio')->nullable();
            $table->unsignedSmallInteger('ano_fim')->nullable();

            $table->timestamps();

            $table->index(['produto_id']);
            $table->index(['marca_id', 'modelo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_aplicacoes');
    }
};
