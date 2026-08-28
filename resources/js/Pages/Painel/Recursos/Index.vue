<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

interface Unidade {
    id: number;
    nome: string;
}

interface Servico {
    id: number;
    nome: string;
    segmento: string;
}

interface Bloqueio {
    id: number;
    data_inicio: string;
    data_fim: string;
    motivo: string | null;
}

interface Intervalo {
    inicio: string;
    fim: string;
}

interface Tecnico {
    id: number;
    name: string;
}

interface Recurso {
    id: number;
    nome: string;
    tipo: 'espaco' | 'pessoa';
    user: Tecnico | null;
    unidade: Unidade | null;
    servicos: Servico[];
    bloqueios: Bloqueio[];
    // Cada dia é uma lista de intervalos — até 3 (ex.: manhã, tarde, noite,
    // ou separados pelo almoço).
    grade_semanal: Record<string, Intervalo[]> | null;
    ativo: boolean;
}

const MAX_INTERVALOS_POR_DIA = 3;

const props = defineProps<{
    recursos: Recurso[];
    unidades: Unidade[];
    servicos: Servico[];
    tecnicos: Tecnico[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const diasSemana: { chave: string; label: string }[] = [
    { chave: 'segunda', label: 'Segunda' },
    { chave: 'terca', label: 'Terça' },
    { chave: 'quarta', label: 'Quarta' },
    { chave: 'quinta', label: 'Quinta' },
    { chave: 'sexta', label: 'Sexta' },
    { chave: 'sabado', label: 'Sábado' },
    { chave: 'domingo', label: 'Domingo' },
];

interface DiaFormulario {
    ativo: boolean;
    intervalos: Intervalo[];
}

function gradeVazia(): Record<string, DiaFormulario> {
    return Object.fromEntries(
        diasSemana.map((d) => [d.chave, { ativo: false, intervalos: [{ inicio: '08:00', fim: '18:00' }] }]),
    );
}

function adicionarIntervalo(diaChave: string) {
    const dia = gradeForm.value[diaChave];
    if (dia.intervalos.length >= MAX_INTERVALOS_POR_DIA) return;

    const ultimo = dia.intervalos[dia.intervalos.length - 1];
    dia.intervalos.push({ inicio: ultimo?.fim ?? '08:00', fim: '18:00' });
}

function removerIntervalo(diaChave: string, indice: number) {
    const dia = gradeForm.value[diaChave];
    if (dia.intervalos.length <= 1) return;

    dia.intervalos.splice(indice, 1);
}

const modalAberto = ref(false);
const recursoEmEdicao = ref<Recurso | null>(null);
const gradeForm = ref(gradeVazia());

const form = useForm({
    nome: '',
    tipo: 'espaco' as 'espaco' | 'pessoa',
    user_id: null as number | null,
    unidade_id: null as number | null,
    servicos: [] as number[],
    ativo: true,
});

// Posto-pessoa vinculado a um técnico: nome vem do usuário, campo fica
// só-leitura pra não divergir do nome de login.
function aoTrocarTecnico() {
    if (form.tipo !== 'pessoa' || form.user_id === null) return;
    const tecnico = props.tecnicos.find((t) => t.id === form.user_id);
    if (tecnico) form.nome = tecnico.name;
}

const buscaServico = ref('');

const servicosFiltrados = computed(() => {
    const termo = buscaServico.value.trim().toLowerCase();
    if (!termo) return props.servicos;

    return props.servicos.filter((s) => s.nome.toLowerCase().includes(termo));
});

function abrirCriacao() {
    recursoEmEdicao.value = null;
    form.reset();
    form.tipo = 'espaco';
    form.user_id = null;
    form.ativo = true;
    gradeForm.value = gradeVazia();
    buscaServico.value = '';
    modalAberto.value = true;
}

function abrirEdicao(recurso: Recurso) {
    recursoEmEdicao.value = recurso;
    form.nome = recurso.nome;
    form.tipo = recurso.tipo;
    form.user_id = recurso.user?.id ?? null;
    form.unidade_id = recurso.unidade?.id ?? null;
    form.servicos = recurso.servicos.map((s) => s.id);
    form.ativo = recurso.ativo;
    buscaServico.value = '';

    const grade = gradeVazia();
    for (const dia of diasSemana) {
        const intervalos = recurso.grade_semanal?.[dia.chave];
        if (intervalos && intervalos.length > 0) {
            grade[dia.chave] = { ativo: true, intervalos: intervalos.map((i) => ({ ...i })) };
        }
    }
    gradeForm.value = grade;

    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    recursoEmEdicao.value = null;
}

function salvar() {
    const grade_semanal: Record<string, Intervalo[]> = {};
    for (const [chave, valor] of Object.entries(gradeForm.value)) {
        if (valor.ativo) {
            grade_semanal[chave] = valor.intervalos;
        }
    }

    form.transform((dados) => ({ ...dados, grade_semanal })).submit(
        recursoEmEdicao.value ? 'put' : 'post',
        recursoEmEdicao.value
            ? route('recursos.update', recursoEmEdicao.value.id)
            : route('recursos.store'),
        { onSuccess: () => fecharModal() },
    );
}

function remover(recurso: Recurso) {
    if (!confirm(`Remover o posto "${recurso.nome}"? Isso não pode ser desfeito.`)) {
        return;
    }

    router.delete(route('recursos.destroy', recurso.id), { preserveScroll: true });
}

// Bloqueios (só existem depois que o recurso já foi criado)
const formBloqueio = useForm({
    data_inicio: '',
    data_fim: '',
    motivo: '',
});

// Depois de um reload parcial, `props.recursos` é substituído por um array
// novo — precisamos resincronizar a referência que o modal está segurando.
function resincronizarRecursoEmEdicao() {
    if (!recursoEmEdicao.value) return;

    const atualizado = props.recursos.find((r) => r.id === recursoEmEdicao.value?.id);
    if (atualizado) recursoEmEdicao.value = atualizado;
}

function adicionarBloqueio() {
    if (!recursoEmEdicao.value) return;

    formBloqueio.post(route('recursos.bloqueios.store', recursoEmEdicao.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            formBloqueio.reset();
            resincronizarRecursoEmEdicao();
        },
    });
}

function removerBloqueio(bloqueio: Bloqueio) {
    router.delete(route('bloqueios.destroy', bloqueio.id), {
        preserveScroll: true,
        onSuccess: () => resincronizarRecursoEmEdicao(),
    });
}
</script>

<template>
    <Head title="Postos de atendimento" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Postos de atendimento e escala</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">
                    Espaços (elevador, box, cabine) e pessoas (mecânico) que atendem um carro por vez, e a grade de cada um.
                </p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Novo posto
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="recurso in props.recursos"
                :key="recurso.id"
                class="rounded-xl border border-surface-200 bg-white p-4"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-sidebar-900">
                            {{ recurso.nome }}
                            <span
                                class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-medium"
                                :class="recurso.tipo === 'pessoa' ? 'bg-blue-50 text-blue-700' : 'bg-surface-200 text-sidebar-800/60'"
                            >
                                {{ recurso.tipo === 'pessoa' ? 'Pessoa' : 'Espaço' }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-sidebar-800/60">{{ recurso.unidade?.nome ?? '—' }}</p>
                    </div>
                    <span
                        class="rounded-full px-2 py-1 text-xs font-medium"
                        :class="recurso.ativo ? 'bg-primary-50 text-primary-700' : 'bg-surface-200 text-sidebar-800/60'"
                    >
                        {{ recurso.ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>

                <p class="mt-3 text-xs text-sidebar-800/60">
                    {{ recurso.servicos.length }} serviço(s) atendido(s)
                    <span v-if="recurso.bloqueios.length"> · {{ recurso.bloqueios.length }} bloqueio(s) ativo(s) </span>
                </p>

                <div class="mt-4 flex gap-3 text-sm">
                    <button type="button" class="font-medium text-primary-600 hover:underline" @click="abrirEdicao(recurso)">
                        Editar
                    </button>
                    <button type="button" class="font-medium text-red-600 hover:underline" @click="remover(recurso)">
                        Remover
                    </button>
                </div>
            </div>

            <p v-if="props.recursos.length === 0" class="text-sm text-sidebar-800/60">
                Nenhum posto de atendimento cadastrado ainda.
            </p>
        </div>

        <!-- Modal: criar / editar -->
        <Modal
            :open="modalAberto"
            :titulo="recursoEmEdicao ? 'Editar posto de atendimento' : 'Novo posto de atendimento'"
            max-width="xl"
            @close="fecharModal"
        >
            <form class="space-y-5" @submit.prevent="salvar">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Tipo</label>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border py-2 text-sm font-medium"
                                    :class="form.tipo === 'espaco' ? 'border-primary-400 bg-primary-50 text-primary-700' : 'border-surface-200 text-sidebar-800'"
                                    @click="form.tipo = 'espaco'; form.user_id = null"
                                >
                                    Espaço (box, elevador, cabine…)
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border py-2 text-sm font-medium"
                                    :class="form.tipo === 'pessoa' ? 'border-primary-400 bg-primary-50 text-primary-700' : 'border-surface-200 text-sidebar-800'"
                                    @click="form.tipo = 'pessoa'"
                                >
                                    Pessoa (mecânico)
                                </button>
                            </div>
                        </div>

                        <div v-if="form.tipo === 'pessoa'">
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Técnico (opcional)</label>
                            <select
                                v-model="form.user_id"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                @change="aoTrocarTecnico"
                            >
                                <option :value="null">Nenhum — sem login no sistema</option>
                                <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <p class="mt-1 text-xs text-sidebar-800/60">
                                Vincular a um usuário técnico já cadastrado preenche o nome sozinho e evita duplicar o cadastro.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome</label>
                                <input
                                    v-model="form.nome"
                                    type="text"
                                    required
                                    :readonly="form.tipo === 'pessoa' && form.user_id !== null"
                                    :class="form.tipo === 'pessoa' && form.user_id !== null ? 'bg-surface-100 text-sidebar-800/70' : ''"
                                    placeholder="Elevador 1, Box 2…"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                                <p v-if="form.errors.nome" class="mt-1 text-xs text-red-600">{{ form.errors.nome }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                                <select
                                    v-model="form.unidade_id"
                                    required
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                >
                                    <option :value="null" disabled>Selecione</option>
                                    <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                                </select>
                                <p v-if="form.errors.unidade_id" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.unidade_id }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="block text-sm font-medium text-sidebar-900">
                                    Serviços atendidos por este posto
                                </label>
                                <span class="text-xs text-sidebar-800/50">{{ form.servicos.length }} selecionado(s)</span>
                            </div>

                            <div class="relative mb-2">
                                <MagnifyingGlassIcon
                                    class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-sidebar-800/40"
                                />
                                <input
                                    v-model="buscaServico"
                                    type="text"
                                    placeholder="Buscar serviço…"
                                    class="w-full rounded-lg border border-surface-200 py-1.5 pr-3 pl-8 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>

                            <div class="grid max-h-48 grid-cols-2 gap-1 overflow-y-auto rounded-lg border border-surface-200 p-2">
                                <label
                                    v-for="servico in servicosFiltrados"
                                    :key="servico.id"
                                    class="flex items-center gap-2 rounded px-2 py-1 text-sm text-sidebar-800 hover:bg-surface-50"
                                >
                                    <input v-model="form.servicos" type="checkbox" :value="servico.id" class="rounded border-surface-200" />
                                    {{ servico.nome }}
                                </label>

                                <p v-if="servicos.length === 0" class="col-span-2 px-2 py-1 text-xs text-sidebar-800/60">
                                    Cadastre serviços no catálogo primeiro.
                                </p>
                                <p v-else-if="servicosFiltrados.length === 0" class="col-span-2 px-2 py-1 text-xs text-sidebar-800/60">
                                    Nenhum serviço encontrado para "{{ buscaServico }}".
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-sidebar-900">Grade semanal</label>
                            <p class="mb-2 text-xs text-sidebar-800/60">
                                Até {{ MAX_INTERVALOS_POR_DIA }} intervalos por dia — use mais de um para separar
                                manhã/tarde pelo almoço, ou adicionar um turno extra.
                            </p>

                            <div class="space-y-2">
                                <div
                                    v-for="dia in diasSemana"
                                    :key="dia.chave"
                                    class="rounded-lg border border-surface-200 px-3 py-2"
                                >
                                    <div class="flex items-center gap-3">
                                        <label class="flex w-24 shrink-0 items-center gap-2 text-sm text-sidebar-800">
                                            <input v-model="gradeForm[dia.chave].ativo" type="checkbox" class="rounded border-surface-200" />
                                            {{ dia.label }}
                                        </label>

                                        <span v-if="!gradeForm[dia.chave].ativo" class="text-xs text-sidebar-800/50">
                                            Fechado
                                        </span>

                                        <div v-else class="flex flex-1 flex-col gap-2">
                                            <div
                                                v-for="(intervalo, indice) in gradeForm[dia.chave].intervalos"
                                                :key="indice"
                                                class="flex items-center gap-2"
                                            >
                                                <input
                                                    v-model="intervalo.inicio"
                                                    type="time"
                                                    class="rounded-lg border border-surface-200 px-2 py-1 text-sm outline-none focus:border-primary-400"
                                                />
                                                <span class="text-xs text-sidebar-800/50">até</span>
                                                <input
                                                    v-model="intervalo.fim"
                                                    type="time"
                                                    class="rounded-lg border border-surface-200 px-2 py-1 text-sm outline-none focus:border-primary-400"
                                                />
                                                <button
                                                    v-if="gradeForm[dia.chave].intervalos.length > 1"
                                                    type="button"
                                                    class="text-xs text-red-600 hover:underline"
                                                    @click="removerIntervalo(dia.chave, indice)"
                                                >
                                                    Remover
                                                </button>
                                            </div>

                                            <button
                                                v-if="gradeForm[dia.chave].intervalos.length < MAX_INTERVALOS_POR_DIA"
                                                type="button"
                                                class="self-start text-xs font-medium text-primary-600 hover:underline"
                                                @click="adicionarIntervalo(dia.chave)"
                                            >
                                                + intervalo
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        v-if="(form.errors as Record<string, string>)[`grade_semanal.${dia.chave}`]"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ (form.errors as Record<string, string>)[`grade_semanal.${dia.chave}`] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <label v-if="recursoEmEdicao" class="flex items-center gap-2 text-sm text-sidebar-800/80">
                            <input v-model="form.ativo" type="checkbox" class="rounded border-surface-200" />
                            Posto ativo
                        </label>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-surface-200 py-2.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                                @click="fecharModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                            >
                                Salvar
                            </button>
                        </div>
                    </form>

                    <!-- Bloqueios: só depois que o recurso já existe -->
                    <div v-if="recursoEmEdicao" class="mt-6 border-t border-surface-200 pt-5">
                        <h3 class="mb-2 text-sm font-semibold text-sidebar-900">Bloqueios</h3>

                        <ul class="mb-3 space-y-1">
                            <li
                                v-for="bloqueio in recursoEmEdicao.bloqueios"
                                :key="bloqueio.id"
                                class="flex items-center justify-between rounded-lg bg-surface-50 px-3 py-2 text-sm"
                            >
                                <span class="text-sidebar-800">
                                    {{ bloqueio.data_inicio }} a {{ bloqueio.data_fim }}
                                    <span v-if="bloqueio.motivo" class="text-sidebar-800/60"> — {{ bloqueio.motivo }}</span>
                                </span>
                                <button type="button" class="text-xs font-medium text-red-600 hover:underline" @click="removerBloqueio(bloqueio)">
                                    Remover
                                </button>
                            </li>
                            <li v-if="recursoEmEdicao.bloqueios.length === 0" class="text-xs text-sidebar-800/60">
                                Nenhum bloqueio cadastrado.
                            </li>
                        </ul>

                        <form class="grid grid-cols-2 gap-2" @submit.prevent="adicionarBloqueio">
                            <input
                                v-model="formBloqueio.data_inicio"
                                type="date"
                                required
                                class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                            <input
                                v-model="formBloqueio.data_fim"
                                type="date"
                                required
                                class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                            <input
                                v-model="formBloqueio.motivo"
                                type="text"
                                placeholder="Motivo (opcional)"
                                class="col-span-2 rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                            <button
                                type="submit"
                                :disabled="formBloqueio.processing"
                                class="col-span-2 rounded-lg border border-surface-200 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                            >
                                Adicionar bloqueio
                            </button>
                        </form>
            </div>
        </Modal>
    </PainelLayout>
</template>
