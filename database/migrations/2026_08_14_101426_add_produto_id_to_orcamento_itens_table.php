<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nulo = item "peça" continua sendo texto livre (compatível com o que já
     * existia antes do módulo de estoque). Preenchido = a aprovação desse
     * item dá baixa automática no estoque desse produto.
     */
    public function up(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->foreignId('produto_id')->nullable()->after('servico_id')->constrained('produtos');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produto_id');
        });
    }
};
