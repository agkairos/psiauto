<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('conta_receber_id')->constrained('contas_receber')->cascadeOnDelete();

            $table->unsignedInteger('numero'); // 1, 2, 3... dentro da conta
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->decimal('valor_recebido', 10, 2)->default(0);

            $table->timestamps();

            $table->index(['conta_receber_id', 'numero']);
            $table->index(['empresa_id', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
