<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            // §04 — "Antecedência mínima: impede marcação em cima da hora."
            // Nulo/0 = sem restrição. Regra da loja, não do recurso.
            $table->unsignedInteger('antecedencia_minima_minutos')->default(0)->after('horario_funcionamento');
        });
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn('antecedencia_minima_minutos');
        });
    }
};
