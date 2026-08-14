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
            // Guardado só com dígitos (14 caracteres) — a formatação
            // (00.000.000/0000-00) é aplicada no front. Ver convenção
            // "Máscaras de campo" em CLAUDE.md.
            $table->string('cnpj', 14)->unique();
            $table->string('email_contato')->nullable();
            $table->string('telefone_contato')->nullable();
            $table->string('logotipo_path')->nullable();

            // §01 — segmentos atendidos: mecânica, elétrica, funilaria, estética, peças
            $table->jsonb('segmentos');

            $table->string('slug')->unique();
            $table->text('descricao_publica')->nullable();

            // §23 — situação da assinatura no plano da plataforma. Sem período de
            // teste: nasce 'ativa' no cadastro; gateway de pagamento (Stripe +
            // Woovi/Pix) ainda não integrado — ver CLAUDE.md.
            $table->enum('situacao_assinatura', ['ativa', 'atraso', 'cancelada'])
                ->default('ativa');
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
