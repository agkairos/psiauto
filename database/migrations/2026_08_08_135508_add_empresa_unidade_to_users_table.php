<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nulo = usuário sem empresa (administrador da plataforma, §23-25).
            $table->foreignId('empresa_id')->nullable()->after('id')
                ->constrained('empresas')->nullOnDelete();

            // Nulo = usuário acessa todas as unidades da empresa (§02).
            $table->foreignId('unidade_id')->nullable()->after('empresa_id')
                ->constrained('unidades')->nullOnDelete();

            $table->boolean('ativo')->default(true)->after('unidade_id');

            $table->index(['empresa_id', 'unidade_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropConstrainedForeignId('unidade_id');
            $table->dropColumn('ativo');
        });
    }
};
