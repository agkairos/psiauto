<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §16 — "tempo médio de permanência do veículo na oficina". `updated_at`
     * muda a cada edição da OS, não serve pra medir isso com precisão —
     * precisa do timestamp exato de quando o status virou 'entregue'.
     */
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->timestamp('entregue_em')->nullable()->after('km_saida');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn('entregue_em');
        });
    }
};
