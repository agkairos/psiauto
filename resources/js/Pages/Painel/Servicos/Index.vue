<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

interface Servico {
    id: number;
    nome: string;
    descricao: string | null;
    segmento: string;
    tipo_preco: 'fixo' | 'a_partir_de' | 'sob_consulta';
    preco: string | null;
    tempo_execucao_minutos: number;
    garantia_dias: number | null;
    garantia_km: number | null;
    comissao_percentual: string | null;
    custo: string | null;
    ativo: boolean;
}

interface SharedProps {
    auth: { permissions: string[] | null };
    [key: string]: unknown;
}

const props = defineProps<{
    servicos: Servico[];
}>();

const page = usePage<SharedProps>();
const permissoes = computed(() => page.props.auth.permissions ?? []);
const podeGerenciar = computed(() => permissoes.value.includes('servicos.gerenciar'));
const podeEditarPreco = computed(
    () => podeGerenciar.value || permissoes.value.includes('servicos.editar_preco'),
);

const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const segmentosDisponiveis = [
    { value: 'mecanica', label: 'Mecânica geral' },
    { value: 'eletrica', label: 'Elétrica automotiva' },
    { value: 'funilaria', label: 'Funilaria' },
    { value: 'estetica', label: 'Estética automotiva' },
    { value: 'pecas', label: 'Casa de peças' },
];

const rotulosSegmento: Record<string, string> = Object.fromEntries(
    segmentosDisponiveis.map((s) => [s.value, s.label]),
);

const tiposPreco = [
    { value: 'fixo', label: 'Valor fixo' },
    { value: 'a_partir_de', label: 'A partir de' },
    { value: 'sob_consulta', label: 'Sob consulta' },
];

function formatarPreco(servico: Servico): string {
    if (servico.tipo_preco === 'sob_consulta' || !servico.preco) {
        return 'Sob consulta';
    }

    const valor = Number(servico.preco).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

    return servico.tipo_preco === 'a_partir_de' ? `A partir de ${valor}` : valor;
}

function formatarDuracao(minutos: number): string {
    const horas = Math.floor(minutos / 60);
    const min = minutos % 60;

    if (horas === 0) return `${min}min`;
    if (min === 0) return `${horas}h`;

    return `${horas}h${min}min`;
}

const modalAberto = ref(false);
const servicoEmEdicao = ref<Servico | null>(null);

const form = useForm({
    nome: '',
    descricao: '',
    segmento: 'mecanica',
    tipo_preco: 'fixo' as Servico['tipo_preco'],
    preco: '',
    tempo_execucao_minutos: 30,
    garantia_dias: '',
    garantia_km: '',
    comissao_percentual: '',
    custo: '',
    ativo: true,
});

function abrirCriacao() {
    servicoEmEdicao.value = null;
    form.reset();
    form.segmento = 'mecanica';
    form.tipo_preco = 'fixo';
    form.tempo_execucao_minutos = 30;
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEdicao(servico: Servico) {
    servicoEmEdicao.value = servico;
    form.nome = servico.nome;
    form.descricao = servico.descricao ?? '';
    form.segmento = servico.segmento;
    form.tipo_preco = servico.tipo_preco;
    form.preco = servico.preco ?? '';
    form.tempo_execucao_minutos = servico.tempo_execucao_minutos;
    form.garantia_dias = servico.garantia_dias?.toString() ?? '';
    form.garantia_km = servico.garantia_km?.toString() ?? '';
    form.comissao_percentual = servico.comissao_percentual ?? '';
    form.custo = servico.custo ?? '';
    form.ativo = servico.ativo;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    servicoEmEdicao.value = null;
}

function salvar() {
    if (servicoEmEdicao.value) {
        form.put(route('servicos.update', servicoEmEdicao.value.id), {
            onSuccess: () => fecharModal(),
        });
    } else {
        form.post(route('servicos.store'), {
            onSuccess: () => fecharModal(),
        });
    }
}

function remover(servico: Servico) {
    if (!confirm(`Remover o serviço "${servico.nome}"? Isso não pode ser desfeito.`)) {
        return;
    }

    router.delete(route('servicos.destroy', servico.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Catálogo de serviços" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Catálogo de serviços</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">
                    O que a sua empresa vende e em quanto tempo entrega.
                </p>
            </div>

            <button
                v-if="podeGerenciar"
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Novo serviço
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="mt-6 overflow-x-auto rounded-xl border border-surface-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-xs uppercase text-sidebar-800/60">
                    <tr>
                        <th class="px-4 py-3">Serviço</th>
                        <th class="px-4 py-3">Segmento</th>
                        <th class="px-4 py-3">Preço</th>
                        <th class="px-4 py-3">Duração</th>
                        <th v-if="podeEditarPreco" class="px-4 py-3">Comissão</th>
                        <th v-if="podeEditarPreco" class="px-4 py-3">Custo</th>
                        <th class="px-4 py-3">Status</th>
                        <th v-if="podeEditarPreco" class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="servico in props.servicos" :key="servico.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ servico.nome }}</p>
                            <p v-if="servico.descricao" class="text-xs text-sidebar-800/60">{{ servico.descricao }}</p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">{{ rotulosSegmento[servico.segmento] }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ formatarPreco(servico) }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ formatarDuracao(servico.tempo_execucao_minutos) }}</td>
                        <td v-if="podeEditarPreco" class="px-4 py-3 text-sidebar-800">
                            {{ servico.comissao_percentual ? `${servico.comissao_percentual}%` : '—' }}
                        </td>
                        <td v-if="podeEditarPreco" class="px-4 py-3 text-sidebar-800">
                            {{ servico.custo ? Number(servico.custo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="servico.ativo ? 'bg-primary-50 text-primary-700' : 'bg-surface-200 text-sidebar-800/60'"
                            >
                                {{ servico.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td v-if="podeEditarPreco" class="px-4 py-3 text-right">
                            <button type="button" class="mr-3 text-sm font-medium text-primary-600 hover:underline" @click="abrirEdicao(servico)">
                                Editar
                            </button>
                            <button
                                v-if="podeGerenciar"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:underline"
                                @click="remover(servico)"
                            >
                                Remover
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.servicos.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="8">
                            Nenhum serviço cadastrado ainda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal: criar / editar -->
        <Modal
            :open="modalAberto"
            :titulo="servicoEmEdicao ? 'Editar serviço' : 'Novo serviço'"
            max-width="lg"
            @close="fecharModal"
        >
            <form class="space-y-4" @submit.prevent="salvar">
                        <template v-if="podeGerenciar">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome</label>
                                <input
                                    v-model="form.nome"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                                <p v-if="form.errors.nome" class="mt-1 text-xs text-red-600">{{ form.errors.nome }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Descrição</label>
                                <textarea
                                    v-model="form.descricao"
                                    rows="2"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Segmento</label>
                                    <select
                                        v-model="form.segmento"
                                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                    >
                                        <option v-for="s in segmentosDisponiveis" :key="s.value" :value="s.value">
                                            {{ s.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Duração (minutos)</label>
                                    <input
                                        v-model.number="form.tempo_execucao_minutos"
                                        type="number"
                                        min="1"
                                        required
                                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                    />
                                    <p v-if="form.errors.tempo_execucao_minutos" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.tempo_execucao_minutos }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Garantia (dias)</label>
                                    <input
                                        v-model="form.garantia_dias"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Garantia (km)</label>
                                    <input
                                        v-model="form.garantia_km"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                    />
                                </div>
                            </div>
                        </template>

                        <!-- Campos de preço — visíveis para quem tem servicos.editar_preco ou servicos.gerenciar -->
                        <hr v-if="podeGerenciar" class="border-surface-200" />

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Tipo de preço</label>
                                <select
                                    v-model="form.tipo_preco"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                >
                                    <option v-for="t in tiposPreco" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>

                            <div v-if="form.tipo_preco !== 'sob_consulta'">
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Preço (R$)</label>
                                <input
                                    v-model="form.preco"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                                <p v-if="form.errors.preco" class="mt-1 text-xs text-red-600">{{ form.errors.preco }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Comissão (%)</label>
                                <input
                                    v-model="form.comissao_percentual"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Custo (R$)</label>
                                <input
                                    v-model="form.custo"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>
                        </div>

                        <label v-if="servicoEmEdicao && podeGerenciar" class="flex items-center gap-2 text-sm text-sidebar-800/80">
                            <input v-model="form.ativo" type="checkbox" class="rounded border-surface-200" />
                            Serviço ativo
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
        </Modal>
    </PainelLayout>
</template>
