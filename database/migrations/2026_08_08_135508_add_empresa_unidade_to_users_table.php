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

            // Login social (Google) — ver docs/login-social.md
            $table->string('google_id')->nullable()->unique()->after('email');

            // Nulo = convite ainda pendente (password também nulo até aceitar,
            // ou o usuário loga direto via Google se o e-mail bater).
            $table->timestamp('convite_aceito_em')->nullable()->after('ativo');
            $table->foreignId('convidado_por')->nullable()->after('convite_aceito_em')
                ->constrained('users')->nullOnDelete();

            $table->index(['empresa_id', 'unidade_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropConstrainedForeignId('unidade_id');
            $table->dropConstrainedForeignId('convidado_por');
            $table->dropColumn(['ativo', 'google_id', 'convite_aceito_em']);
        });
    }
};
