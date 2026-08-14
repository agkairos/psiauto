<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();

            // Elevador, box, cabine de pintura, mecânico... (§04)
            $table->string('nome');

            // §04 — grade semanal: horário de início/fim por dia da semana.
            // Estrutura: {"segunda": {"inicio": "08:00", "fim": "18:00"}, ...}
            // Dia ausente = recurso não atende naquele dia.
            $table->jsonb('grade_semanal')->nullable();

            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'unidade_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
