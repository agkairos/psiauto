<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            $table->string('razao_social');
            $table->string('nome_fantasia');
            $table->string('cnpj', 18)->unique();
            $table->string('email_contato')->nullable();
            $table->string('telefone_contato')->nullable();
            $table->string('logotipo_path')->nullable();

            // §01 — segmentos atendidos: mecânica, elétrica, funilaria, estética, peças
            $table->jsonb('segmentos');

            $table->string('slug')->unique();
            $table->text('descricao_publica')->nullable();

            // §23 — situação da assinatura no plano da plataforma
            $table->enum('situacao_assinatura', ['teste', 'ativa', 'atraso', 'cancelada'])
                ->default('teste');
            $table->timestamp('aprovada_em')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
