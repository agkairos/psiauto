<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global da plataforma (§23) — não é por empresa. Alimentado
     * pelo comando `fipe:importar`, que busca da Tabela FIPE.
     */
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');

            // carro | moto | caminhao — a FIPE separa os 3 catálogos.
            $table->string('tipo_veiculo', 10);

            // Código da marca na API da FIPE, usado para reimportar/atualizar
            // sem duplicar.
            $table->string('fipe_codigo', 20);

            $table->timestamps();

            $table->unique(['tipo_veiculo', 'fipe_codigo']);
            $table->index(['tipo_veiculo', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas');
    }
};
