<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §04 — "Bloqueios: feriado, férias, manutenção do equipamento ou
     * qualquer indisponibilidade."
     */
    public function up(): void
    {
        Schema::create('recurso_bloqueios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();

            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('motivo')->nullable();

            $table->timestamps();

            $table->index(['recurso_id', 'data_inicio', 'data_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurso_bloqueios');
    }
};
