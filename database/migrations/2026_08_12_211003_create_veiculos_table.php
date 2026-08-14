<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Catálogo global (§23) — ver docs/fipe.md.
            $table->foreignId('marca_id')->constrained('marcas');
            $table->foreignId('modelo_id')->constrained('modelos');

            $table->string('placa', 8);
            $table->string('chassi', 30)->nullable();
            $table->unsignedSmallInteger('ano_fabricacao')->nullable();
            $table->unsignedSmallInteger('ano_modelo')->nullable();
            $table->string('versao')->nullable();
            $table->string('cor')->nullable();
            $table->unsignedInteger('quilometragem_atual')->nullable();

            $table->text('observacoes_internas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'placa']);
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
