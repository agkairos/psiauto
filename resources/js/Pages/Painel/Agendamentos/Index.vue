<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/vue/24/outline';
import ClienteBusca from '@/Components/ClienteBusca.vue';

interface Unidade {
    id: number;
    nome: string;
}

interface Intervalo {
    inicio: string;
    fim: string;
}

interface Recurso {
    id: number;
    nome: string;
    grade_semanal: Record<string, Intervalo[]> | null;
    servicos: { id: number; nome: string }[];
}

interface MarcaModelo {
    id: number;
    nome: string;
}

interface Veiculo {
    id: number;
    placa: string;
    marca: MarcaModelo;
    modelo: MarcaModelo;
}

interface Cliente {
    id: number;
    nome: string;
    veiculos: Veiculo[];
}

interface Servico {
    id: number;
    nome: string;
    tempo_execucao_minutos: number;
}

interface Agendamento {
    id: number;
    recurso_id: number;
    data: string;
    hora_inicio: string;
    hora_fim: string;
    status: string;
    observacoes_cliente: string | null;
    motivo_cancelamento: string | null;
    cliente: { id: number; nome: string };
    veiculo: { id: number; placa: string; marca: MarcaModelo; modelo: MarcaModelo };
    servico: { id: number; nome: string; tempo_execucao_minutos: number };
}

const props = defineProps<{
    data: string;
    unidadeId: number;
    unidades: Unidade[];
    recursos: Recurso[];
    agendamentos: Agendamento[];
    servicos: Servico[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const DIAS_SEMANA = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
const PASSO_MINUTOS = 30;

const rotulosStatus: Record<string, string> = {
    solicitado: 'Solicitado',
    confirmado: 'Confirmado',
    recebido: 'Veículo recebido',
    em_execucao: 'Em execução',
    concluido: 'Concluído',
    cancelado: 'Cancelado',
    nao_compareceu: 'Não compareceu',
};

const coresStatus: Record<string, string> = {
    solicitado: 'bg-surface-200 text-sidebar-800 border-surface-300',
    confirmado: 'bg-blue-50 text-blue-700 border-blue-200',
    recebido: 'bg-purple-50 text-purple-700 border-purple-200',
    em_execucao: 'bg-primary-50 text-primary-800 border-primary-300',
    concluido: 'bg-green-50 text-green-700 border-green-200',
    cancelado: 'bg-red-50 text-red-700 border-red-200 line-through opacity-70',
    nao_compareceu: 'bg-red-50 text-red-700 border-red-200',
};

const PROXIMO_STATUS: Record<string, string | null> = {
    solicitado: 'confirmado',
    confirmado: 'recebido',
    recebido: 'em_execucao',
    em_execucao: 'concluido',
    concluido: null,
    cancelado: null,
    nao_compareceu: null,
};

function diaChaveDeData(data: string): string {
    const [ano, mes, dia] = data.split('-').map(Number);
    return DIAS_SEMANA[new Date(ano, mes - 1, dia).getDay()];
}

const diaChave = computed(() => diaChaveDeData(props.data));

function minutosDe(hora: string): number {
    const [h, m] = hora.split(':').map(Number);
    return h * 60 + m;
}

function horaDe(minutos: number): string {
    const h = Math.floor(minutos / 60)
        .toString()
        .padStart(2, '0');
    const m = (minutos % 60).toString().padStart(2, '0');
    return `${h}:${m}`;
}

// Intervalo de horários exibido na grade — a união dos horários de todos os
// recursos nesse dia, com folga de 1h pra cima/baixo. Sem recurso aberto,
// cai num padrão razoável (07h–19h).
const limites = computed(() => {
    let min = Infinity;
    let max = -Infinity;

    for (const recurso of props.recursos) {
        for (const intervalo of recurso.grade_semanal?.[diaChave.value] ?? []) {
            min = Math.min(min, minutosDe(intervalo.inicio));
            max = Math.max(max, minutosDe(intervalo.fim));
        }
    }

    if (!isFinite(min) || !isFinite(max)) {
        return { min: 7 * 60, max: 19 * 60 };
    }

    return { min: Math.max(0, min - 60), max: Math.min(24 * 60, max + 60) };
});

const slots = computed(() => {
    const lista: number[] = [];
    for (let m = limites.value.min; m < limites.value.max; m += PASSO_MINUTOS) {
        lista.push(m);
    }
    return lista;
});

function slotAberto(recurso: Recurso, minutoSlot: number): boolean {
    const intervalos = recurso.grade_semanal?.[diaChave.value] ?? [];
    return intervalos.some((iv) => minutoSlot >= minutosDe(iv.inicio) && minutoSlot < minutosDe(iv.fim));
}

function agendamentosDoRecurso(recursoId: number): Agendamento[] {
    return props.agendamentos.filter((a) => a.recurso_id === recursoId && a.status !== 'cancelado');
}

function estiloBloco(agendamento: Agendamento, indiceRecurso: number): Record<string, string> {
    const inicio = minutosDe(agendamento.hora_inicio);
    const fim = minutosDe(agendamento.hora_fim);
    // +2: linha 1 é o cabeçalho, e grid-row/column no CSS começam em 1.
    const linhaInicio = Math.round((inicio - limites.value.min) / PASSO_MINUTOS) + 2;
    const span = Math.max(1, Math.round((fim - inicio) / PASSO_MINUTOS));

    return {
        gridColumn: String(indiceRecurso + 2),
        gridRow: `${linhaInicio} / span ${span}`,
    };
}

// Navegação de data / unidade
function irParaData(novaData: string) {
    router.get(route('agendamentos.index'), { data: novaData, unidade_id: props.unidadeId }, { preserveState: true });
}

function diaAnterior() {
    const d = new Date(props.data + 'T00:00:00');
    d.setDate(d.getDate() - 1);
    irParaData(d.toISOString().slice(0, 10));
}

function proximoDia() {
    const d = new Date(props.data + 'T00:00:00');
    d.setDate(d.getDate() + 1);
    irParaData(d.toISOString().slice(0, 10));
}

function trocarUnidade(unidadeId: number) {
    router.get(route('agendamentos.index'), { data: props.data, unidade_id: unidadeId }, { preserveState: true });
}

// Criar agendamento
const modalCriarAberto = ref(false);

// "data_agendamento" e não "data": useForm() já tem um método interno
// chamado data(), um campo com esse nome colide e quebra o form.
const form = useForm({
    cliente_id: null as number | null,
    veiculo_id: null as number | null,
    servico_id: null as number | null,
    recurso_id: null as number | null,
    data_agendamento: props.data,
    hora_inicio: '',
    observacoes_cliente: '',
});

const veiculosDoClienteSelecionado = ref<Veiculo[]>([]);

function aoSelecionarCliente(cliente: Cliente | null) {
    veiculosDoClienteSelecionado.value = cliente?.veiculos ?? [];
    form.veiculo_id = null;
}

function abrirCriacao(recursoId?: number, minutoSlot?: number) {
    form.reset();
    form.data_agendamento = props.data;
    veiculosDoClienteSelecionado.value = [];
    if (recursoId) form.recurso_id = recursoId;
    if (minutoSlot !== undefined) form.hora_inicio = horaDe(minutoSlot);
    modalCriarAberto.value = true;
}

function salvarNovo() {
    // O backend espera o campo como "data" — só o form no front precisa do
    // nome diferente pra não colidir com o método data() do useForm().
    form.transform(({ data_agendamento, ...resto }) => ({ ...resto, data: data_agendamento })).post(
        route('agendamentos.store'),
        {
            onSuccess: () => {
                modalCriarAberto.value = false;
            },
        },
    );
}

// Detalhe / status / cancelamento
const agendamentoSelecionado = ref<Agendamento | null>(null);

function abrirDetalhe(agendamento: Agendamento) {
    agendamentoSelecionado.value = agendamento;
}

function avancarStatus(agendamento: Agendamento) {
    const proximo = PROXIMO_STATUS[agendamento.status];
    if (!proximo) return;

    router.patch(
        route('agendamentos.status', agendamento.id),
        { status: proximo },
        { preserveScroll: true, onSuccess: () => (agendamentoSelecionado.value = null) },
    );
}

function marcarNaoCompareceu(agendamento: Agendamento) {
    router.patch(
        route('agendamentos.status', agendamento.id),
        { status: 'nao_compareceu' },
        { preserveScroll: true, onSuccess: () => (agendamentoSelecionado.value = null) },
    );
}

function cancelar(agendamento: Agendamento) {
    const motivo = prompt('Motivo do cancelamento (opcional):') ?? '';

    router.post(
        route('agendamentos.cancelar', agendamento.id),
        { motivo_cancelamento: motivo },
        { preserveScroll: true, onSuccess: () => (agendamentoSelecionado.value = null) },
    );
}
</script>

<template>
    <Head title="Agendamentos" />

    <PainelLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Agendamentos</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Agenda do dia por posto de atendimento.</p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao()"
            >
                <PlusIcon class="h-4 w-4" />
                Novo agendamento
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 rounded-lg border border-surface-200 bg-white p-1">
                <button type="button" class="rounded p-1 hover:bg-surface-100" @click="diaAnterior">
                    <ChevronLeftIcon class="h-4 w-4 text-sidebar-800" />
                </button>
                <input
                    type="date"
                    :value="props.data"
                    class="border-0 px-2 py-1 text-sm outline-none"
                    @change="irParaData(($event.target as HTMLInputElement).value)"
                />
                <button type="button" class="rounded p-1 hover:bg-surface-100" @click="proximoDia">
                    <ChevronRightIcon class="h-4 w-4 text-sidebar-800" />
                </button>
            </div>

            <select
                :value="props.unidadeId"
                class="rounded-lg border border-surface-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400"
                @change="trocarUnidade(Number(($event.target as HTMLSelectElement).value))"
            >
                <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
            </select>
        </div>

        <div v-if="recursos.length === 0" class="mt-6 text-sm text-sidebar-800/60">
            Nenhum posto ativo nessa unidade. Cadastre em Postos e escala.
        </div>

        <!--
            Um grid ÚNICO e plano — cabeçalho, rótulos de hora, células
            clicáveis e blocos de agendamento são todos filhos diretos dele,
            posicionados por grid-column/grid-row. Grid aninhado dentro de
            grid não funciona bem aqui: a coluna filha não herda a altura das
            linhas do grid pai a menos que receba `grid-row` explícito, e sem
            isso ela colapsa (foi o bug do "recursos não aparecem").
        -->
        <div v-else class="mt-4 overflow-x-auto rounded-xl border border-surface-200 bg-white">
            <div
                class="relative grid min-w-160"
                :style="{
                    gridTemplateColumns: `4rem repeat(${recursos.length}, minmax(9rem, 1fr))`,
                    gridTemplateRows: `auto repeat(${slots.length}, 2.25rem)`,
                }"
            >
                <!-- Cabeçalho -->
                <div class="sticky top-0 z-10 border-b border-surface-200 bg-surface-50" style="grid-column: 1; grid-row: 1"></div>
                <div
                    v-for="(recurso, indiceRecurso) in recursos"
                    :key="'cab-' + recurso.id"
                    class="sticky top-0 z-10 border-b border-l border-surface-200 bg-surface-50 px-2 py-2 text-xs font-semibold text-sidebar-900"
                    :style="{ gridColumn: indiceRecurso + 2, gridRow: 1 }"
                >
                    {{ recurso.nome }}
                </div>

                <!-- Rótulos de hora -->
                <div
                    v-for="(slot, indiceSlot) in slots"
                    :key="'hora-' + slot"
                    class="border-b border-surface-100 px-1 py-1 text-right text-[10px] text-sidebar-800/50"
                    :style="{ gridColumn: 1, gridRow: indiceSlot + 2 }"
                >
                    {{ horaDe(slot) }}
                </div>

                <!-- Células clicáveis (uma por recurso x horário) -->
                <template v-for="(recurso, indiceRecurso) in recursos" :key="'celulas-' + recurso.id">
                    <button
                        v-for="(slot, indiceSlot) in slots"
                        :key="slot"
                        type="button"
                        class="border-b border-l border-surface-100 text-left"
                        :class="slotAberto(recurso, slot) ? 'hover:bg-primary-50/60' : 'bg-surface-100/70'"
                        :disabled="!slotAberto(recurso, slot)"
                        :style="{ gridColumn: indiceRecurso + 2, gridRow: indiceSlot + 2 }"
                        @click="abrirCriacao(recurso.id, slot)"
                    />
                </template>

                <!-- Blocos de agendamento, por cima das células -->
                <template v-for="(recurso, indiceRecurso) in recursos" :key="'ags-' + recurso.id">
                    <button
                        v-for="agendamento in agendamentosDoRecurso(recurso.id)"
                        :key="agendamento.id"
                        type="button"
                        class="z-[1] mx-0.5 overflow-hidden rounded-md border px-1.5 py-1 text-left text-[11px] leading-tight shadow-sm"
                        :class="coresStatus[agendamento.status]"
                        :style="estiloBloco(agendamento, indiceRecurso)"
                        @click.stop="abrirDetalhe(agendamento)"
                    >
                        <p class="truncate font-semibold">{{ agendamento.cliente.nome }}</p>
                        <p class="truncate">{{ agendamento.servico.nome }}</p>
                        <p class="truncate text-[10px] opacity-70">
                            {{ agendamento.veiculo.placa }} · {{ agendamento.hora_inicio }}
                        </p>
                    </button>
                </template>
            </div>
        </div>

        <!-- Modal: novo agendamento -->
        <Modal :open="modalCriarAberto" titulo="Novo agendamento" max-width="lg" @close="modalCriarAberto = false">
            <form class="space-y-4" @submit.prevent="salvarNovo">
                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Cliente</label>
                    <ClienteBusca v-model="form.cliente_id" @select="aoSelecionarCliente" />
                    <p v-if="form.errors.cliente_id" class="mt-1 text-xs text-red-600">{{ form.errors.cliente_id }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Veículo</label>
                    <select
                        v-model="form.veiculo_id"
                        required
                        :disabled="!form.cliente_id"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 disabled:bg-surface-100"
                    >
                        <option :value="null" disabled>Selecione</option>
                        <option v-for="v in veiculosDoClienteSelecionado" :key="v.id" :value="v.id">
                            {{ v.marca.nome }} {{ v.modelo.nome }} — {{ v.placa }}
                        </option>
                    </select>
                    <p v-if="form.errors.veiculo_id" class="mt-1 text-xs text-red-600">{{ form.errors.veiculo_id }}</p>
                    <p v-if="form.cliente_id && veiculosDoClienteSelecionado.length === 0" class="mt-1 text-xs text-sidebar-800/60">
                        Esse cliente ainda não tem veículo cadastrado.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Serviço</label>
                    <select
                        v-model="form.servico_id"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    >
                        <option :value="null" disabled>Selecione</option>
                        <option v-for="s in servicos" :key="s.id" :value="s.id">
                            {{ s.nome }} ({{ s.tempo_execucao_minutos }}min)
                        </option>
                    </select>
                    <p v-if="form.errors.servico_id" class="mt-1 text-xs text-red-600">{{ form.errors.servico_id }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Posto de atendimento</label>
                    <select
                        v-model="form.recurso_id"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    >
                        <option :value="null" disabled>Selecione</option>
                        <option v-for="r in recursos" :key="r.id" :value="r.id">{{ r.nome }}</option>
                    </select>
                    <p v-if="form.errors.recurso_id" class="mt-1 text-xs text-red-600">{{ form.errors.recurso_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Data</label>
                        <input
                            v-model="form.data_agendamento"
                            type="date"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="(form.errors as Record<string, string>).data" class="mt-1 text-xs text-red-600">
                            {{ (form.errors as Record<string, string>).data }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Horário</label>
                        <input
                            v-model="form.hora_inicio"
                            type="time"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="form.errors.hora_inicio" class="mt-1 text-xs text-red-600">{{ form.errors.hora_inicio }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Observações do cliente</label>
                    <textarea
                        v-model="form.observacoes_cliente"
                        rows="2"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-surface-200 py-2.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                        @click="modalCriarAberto = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                    >
                        Agendar
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal: detalhe / status -->
        <Modal :open="agendamentoSelecionado !== null" titulo="Agendamento" @close="agendamentoSelecionado = null">
            <div v-if="agendamentoSelecionado" class="space-y-3 text-sm">
                <span
                    class="inline-block rounded-full border px-2 py-1 text-xs font-medium"
                    :class="coresStatus[agendamentoSelecionado.status]"
                >
                    {{ rotulosStatus[agendamentoSelecionado.status] }}
                </span>

                <p><strong>Cliente:</strong> {{ agendamentoSelecionado.cliente.nome }}</p>
                <p>
                    <strong>Veículo:</strong> {{ agendamentoSelecionado.veiculo.marca.nome }}
                    {{ agendamentoSelecionado.veiculo.modelo.nome }} — {{ agendamentoSelecionado.veiculo.placa }}
                </p>
                <p><strong>Serviço:</strong> {{ agendamentoSelecionado.servico.nome }}</p>
                <p><strong>Horário:</strong> {{ agendamentoSelecionado.hora_inicio }} – {{ agendamentoSelecionado.hora_fim }}</p>
                <p v-if="agendamentoSelecionado.observacoes_cliente">
                    <strong>Observações:</strong> {{ agendamentoSelecionado.observacoes_cliente }}
                </p>
                <p v-if="agendamentoSelecionado.motivo_cancelamento" class="text-red-600">
                    <strong>Motivo do cancelamento:</strong> {{ agendamentoSelecionado.motivo_cancelamento }}
                </p>

                <div
                    v-if="!['concluido', 'cancelado', 'nao_compareceu'].includes(agendamentoSelecionado.status)"
                    class="flex flex-wrap gap-2 pt-3"
                >
                    <button
                        v-if="PROXIMO_STATUS[agendamentoSelecionado.status]"
                        type="button"
                        class="rounded-lg bg-primary-400 px-3 py-1.5 text-xs font-semibold text-sidebar-950 hover:opacity-90"
                        @click="avancarStatus(agendamentoSelecionado)"
                    >
                        Avançar para "{{ rotulosStatus[PROXIMO_STATUS[agendamentoSelecionado.status]!] }}"
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-surface-200 px-3 py-1.5 text-xs font-medium text-sidebar-800 hover:bg-surface-50"
                        @click="marcarNaoCompareceu(agendamentoSelecionado)"
                    >
                        Não compareceu
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                        @click="cancelar(agendamentoSelecionado)"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </Modal>
    </PainelLayout>
</template>
