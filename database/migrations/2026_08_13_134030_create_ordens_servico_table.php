<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §07 — do recebimento à entrega. Etapa 1 (esta migration): abertura +
     * checklist de entrada. Itens de orçamento/aprovação e as etapas
     * aguardando_aprovacao→...→entregue entram numa etapa seguinte, quando o
     * módulo de orçamento existir — por enquanto toda OS nasce e permanece
     * em 'aberta'.
     */
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();

            // Nulo = OS avulsa, aberta sem agendamento prévio (cliente sem
            // hora marcada) — decisão confirmada com o usuário.
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();

            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('veiculo_id')->constrained('veiculos');

            // aberta = checklist/diagnóstico em andamento, ainda sem
            // orçamento. Demais status da spec entram na próxima etapa.
            $table->string('status', 20)->default('aberta');

            // §07 — checklist de entrada: km, combustível, avarias, objetos
            // deixados, confirmação do cliente. Estrutura livre por ora (a
            // spec não define schema fixo por segmento) — ver docs/checklist-os.md.
            $table->jsonb('checklist_entrada')->nullable();

            $table->text('reclamacao_cliente')->nullable();
            $table->text('diagnostico_tecnico')->nullable();

            $table->unsignedInteger('km_saida')->nullable();

            $table->foreignId('aberta_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'unidade_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servico');
    }
};
