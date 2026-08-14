<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §04 — "Serviços por recurso: define o que cada posição atende,
     * evitando marcar pintura em box de mecânica."
     */
    public function up(): void
    {
        Schema::create('recurso_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();

            $table->unique(['recurso_id', 'servico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurso_servico');
    }
};
