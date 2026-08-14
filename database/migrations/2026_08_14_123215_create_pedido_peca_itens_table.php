<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_peca_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('pedido_peca_id')->constrained('pedidos_peca')->cascadeOnDelete();
            // Nulo = peça não cadastrada no catálogo interno (texto livre) —
            // não gera reserva de estoque, só registro do orçamento.
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();

            $table->string('descricao');
            $table->integer('quantidade');

            $table->string('disponibilidade', 15)->nullable();
            // em_estoque | sob_encomenda | indisponivel — nulo até ser precificado
            $table->decimal('preco_unitario', 10, 2)->nullable();
            $table->integer('prazo_entrega_dias')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_peca_itens');
    }
};
