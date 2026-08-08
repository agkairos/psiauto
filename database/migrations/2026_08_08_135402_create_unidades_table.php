<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();

            // Sem PostGIS por enquanto (não instalado no ambiente local) — ver CLAUDE.md.
            // Migrar para coluna geography ao implementar busca por proximidade (§18).
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            // §01 — horário de funcionamento por dia da semana, com intervalo de almoço
            $table->jsonb('horario_funcionamento')->nullable();

            $table->boolean('ativa')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'ativa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
