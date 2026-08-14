<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige o tamanho de `cep` para 8 (só dígitos). A convenção do projeto
     * ("Máscaras de campo" no CLAUDE.md) guarda campos mascarados sem
     * pontuação — 9 caracteres sobrava espaço para o hífen que não é mais
     * salvo. Tabela `unidades` ainda não tem nenhum registro em produção.
     */
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn('cep');
        });

        Schema::table('unidades', function (Blueprint $table) {
            $table->string('cep', 8)->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn('cep');
        });

        Schema::table('unidades', function (Blueprint $table) {
            $table->string('cep', 9)->nullable()->after('nome');
        });
    }
};
