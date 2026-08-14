<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §07 — "serviços e peças aplicados, com responsável por cada item".
     * Base pra comissão (§13) e pro indicador de produção por técnico (§16).
     */
    public function up(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->foreignId('responsavel_id')->nullable()->after('produto_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsavel_id');
        });
    }
};
