<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import echo from '@/echo';

interface Unidade {
    id: number;
    nome: string;
}

interface Agendamento {
    id: number;
    unidade_id: number;
    recurso_id: number;
    data: string;
    hora_inicio: string;
    hora_fim: string;
    status: string;
    cliente: { id: number; nome: string };
    veiculo: { id: number; placa: string };
    servico: { id: number; nome: string };
    recurso: { id: number; nome: string };
}

interface EventoStatusAlterado {
    agendamentoId: number;
    empresaId: number;
    unidadeId: number;
    recursoId: number;
    status: string;
    data: string;
    horaInicio: string;
    horaFim: string;
    clienteNome: string;
    veiculoPlaca: string;
    servicoNome: string;
}

interface SharedProps {
    auth: { empresa: { id: number } | null };
    [key: string]: unknown;
}

const props = defineProps<{
    agendamentos: Agendamento[];
    unidadeId: number | null;
    unidades: Unidade[];
}>();

const page = usePage<SharedProps>();

// Cópia reativa local — atualizada tanto pela carga inicial (Inertia) quanto
// pelos eventos ao vivo (Reverb), sem precisar de reload de página.
const lista = ref<Agendamento[]>([...props.agendamentos]);

const STATUS_QUE_LIBERAM = ['cancelado', 'nao_compareceu'];

const COLUNAS = [
    { chave: 'aguardando', titulo: 'Aguardando', status: ['solicitado', 'confirmado'] },
    { chave: 'recebido', titulo: 'Veículo recebido', status: ['recebido'] },
    { chave: 'em_execucao', titulo: 'Em execução', status: ['em_execucao'] },
    { chave: 'concluido', titulo: 'Concluído', status: ['concluido'] },
];

function agendamentosDaColuna(statusDaColuna: string[]): Agendamento[] {
    return lista.value
        .filter((a) => statusDaColuna.includes(a.status))
        .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
}

const PROXIMO_STATUS: Record<string, string | null> = {
    solicitado: 'confirmado',
    confirmado: 'recebido',
    recebido: 'em_execucao',
    em_execucao: 'concluido',
    concluido: null,
};

function avancar(agendamento: Agendamento) {
    const proximo = PROXIMO_STATUS[agendamento.status];
    if (!proximo) return;

    router.patch(
        route('agendamentos.status', agendamento.id),
        { status: proximo },
        { preserveScroll: true, preserveState: true },
    );
}

function trocarUnidade(unidadeId: string) {
    router.get(route('painel-dia.index'), unidadeId ? { unidade_id: unidadeId } : {}, { preserveState: true });
}

// Tempo real: canal privado por empresa (ver routes/channels.php e skill
// realtime-status). Some da lista quando o status vira cancelado/não
// compareceu, atualiza in-place quando muda, ignora eventos de outra
// unidade quando há filtro ativo.
let canalInscrito: string | null = null;

onMounted(() => {
    const empresaId = page.props.auth.empresa?.id;
    if (!empresaId) return;

    canalInscrito = `empresa.${empresaId}.painel-dia`;

    const hojeISO = new Date().toLocaleDateString('sv-SE'); // YYYY-MM-DD no fuso local

    echo.private(canalInscrito).listen('.agendamento.status-alterado', (evento: EventoStatusAlterado) => {
        if (evento.data !== hojeISO) {
            return;
        }

        if (props.unidadeId && evento.unidadeId !== props.unidadeId) {
            return;
        }

        if (STATUS_QUE_LIBERAM.includes(evento.status)) {
            lista.value = lista.value.filter((a) => a.id !== evento.agendamentoId);
            return;
        }

        const existente = lista.value.find((a) => a.id === evento.agendamentoId);
        if (existente) {
            existente.status = evento.status;
            existente.hora_inicio = evento.horaInicio;
            existente.hora_fim = evento.horaFim;
        } else {
            lista.value.push({
                id: evento.agendamentoId,
                unidade_id: evento.unidadeId,
                recurso_id: evento.recursoId,
                data: evento.data,
                hora_inicio: evento.horaInicio,
                hora_fim: evento.horaFim,
                status: evento.status,
                cliente: { id: 0, nome: evento.clienteNome },
                veiculo: { id: 0, placa: evento.veiculoPlaca },
                servico: { id: 0, nome: evento.servicoNome },
                recurso: { id: evento.recursoId, nome: '' },
            });
        }
    });
});

onUnmounted(() => {
    if (canalInscrito) echo.leave(canalInscrito);
});
</script>

<template>
    <Head title="Painel do dia" />

    <PainelLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Painel do dia</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Fila de hoje, atualizando ao vivo.</p>
            </div>

            <select
                :value="props.unidadeId ?? ''"
                class="rounded-lg border border-surface-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400"
                @change="trocarUnidade(($event.target as HTMLSelectElement).value)"
            >
                <option value="">Todas as unidades</option>
                <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
            </select>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="coluna in COLUNAS" :key="coluna.chave" class="rounded-xl border border-surface-200 bg-white p-3">
                <h2 class="mb-3 flex items-center justify-between text-xs font-semibold tracking-wide text-sidebar-800/70 uppercase">
                    {{ coluna.titulo }}
                    <span class="rounded-full bg-surface-100 px-2 py-0.5 text-[10px] font-medium text-sidebar-800/60">
                        {{ agendamentosDaColuna(coluna.status).length }}
                    </span>
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="agendamento in agendamentosDaColuna(coluna.status)"
                        :key="agendamento.id"
                        class="rounded-lg border border-surface-200 bg-surface-50 p-2.5 text-sm"
                    >
                        <p class="font-medium text-sidebar-900">{{ agendamento.cliente.nome }}</p>
                        <p class="text-xs text-sidebar-800/70">{{ agendamento.servico.nome }}</p>
                        <p class="mt-1 text-[11px] text-sidebar-800/50">
                            {{ agendamento.veiculo.placa }} · {{ agendamento.recurso.nome }} · {{ agendamento.hora_inicio }}
                        </p>

                        <button
                            v-if="PROXIMO_STATUS[agendamento.status]"
                            type="button"
                            class="mt-2 w-full rounded-md bg-primary-400 py-1 text-[11px] font-semibold text-sidebar-950 hover:opacity-90"
                            @click="avancar(agendamento)"
                        >
                            Avançar
                        </button>
                    </div>

                    <p v-if="agendamentosDaColuna(coluna.status).length === 0" class="text-xs text-sidebar-800/50">
                        Vazio.
                    </p>
                </div>
            </div>
        </div>
    </PainelLayout>
</template>
