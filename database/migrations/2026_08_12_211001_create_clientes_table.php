<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §09 — cliente cadastrado pela empresa (balcão). O vínculo com o
     * "cliente da plataforma" (§17, conta do motorista no app) é um
     * relacionamento futuro, fora do escopo deste momento.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->string('telefone', 11)->nullable();
            $table->string('email')->nullable();

            // Só dígitos — 11 (CPF) ou 14 (CNPJ). Ver convenção "Máscaras de
            // campo" no CLAUDE.md.
            $table->string('cpf_cnpj', 14)->nullable();

            $table->text('observacoes_internas')->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'ativo']);
            $table->index(['empresa_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
