<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();

            // Por empresa, não por unidade — ver docs/escopo-empresa-unidade.md
            // (preço único por empresa é o default adotado; revisitar se
            // surgir caso real de preço variando por loja).
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->text('descricao')->nullable();

            // §01/§03 — um segmento por serviço (mecânica, elétrica, funilaria,
            // estética, peças), consistente com os segmentos da empresa.
            $table->string('segmento', 20);

            $table->enum('tipo_preco', ['fixo', 'a_partir_de', 'sob_consulta']);
            $table->decimal('preco', 10, 2)->nullable();

            // Tempo de execução em minutos — ocupa a agenda (§04).
            $table->unsignedInteger('tempo_execucao_minutos');

            $table->unsignedInteger('garantia_dias')->nullable();
            $table->unsignedInteger('garantia_km')->nullable();

            $table->decimal('comissao_percentual', 5, 2)->nullable();
            $table->decimal('custo', 10, 2)->nullable();

            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'ativo']);
            $table->index(['empresa_id', 'segmento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
