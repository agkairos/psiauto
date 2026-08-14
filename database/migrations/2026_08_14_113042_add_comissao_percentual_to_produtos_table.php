<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §13 — "comissões calculadas por serviço OU por peça". Servico já tem
     * comissao_percentual (§03); faltava em Produto.
     */
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('comissao_percentual', 5, 2)->default(0)->after('preco_venda');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('comissao_percentual');
        });
    }
};
