<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

interface Pagamento {
    id: number;
    valor: string;
    data: string;
}

interface ContaPagar {
    id: number;
    fornecedor: string | null;
    descricao: string;
    categoria: string | null;
    valor: string;
    valor_pago: string;
    status: 'pendente' | 'parcial' | 'paga' | 'atrasada';
    data_vencimento: string;
    recorrente: boolean;
    pagamentos: Pagamento[];
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    contas: Paginado<ContaPagar>;
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const rotulosStatus: Record<string, string> = {
    pendente: 'Pendente',
    parcial: 'Parcial',
    paga: 'Paga',
    atrasada: 'Atrasada',
};

const coresStatus: Record<string, string> = {
    pendente: 'bg-surface-200 text-sidebar-800',
    parcial: 'bg-amber-50 text-amber-700',
    paga: 'bg-green-50 text-green-700',
    atrasada: 'bg-red-50 text-red-700',
};

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

// Criar conta
const modalCriarAberto = ref(false);
const form = useForm({
    fornecedor: '',
    descricao: '',
    categoria: '',
    valor: '',
    data_vencimento: '',
    recorrente: false,
    periodicidade: 'mensal',
});

function abrirCriacao() {
    form.reset();
    modalCriarAberto.value = true;
}

function salvar() {
    form.post(route('contas-pagar.store'), { onSuccess: () => (modalCriarAberto.value = false) });
}

function remover(conta: ContaPagar) {
    if (!confirm(`Remover a conta "${conta.descricao}"?`)) return;
    router.delete(route('contas-pagar.destroy', conta.id), { preserveScroll: true });
}

// Baixa
const contaEmBaixa = ref<ContaPagar | null>(null);
const formPagamento = useForm({
    valor: '',
    data: new Date().toISOString().slice(0, 10),
});

function abrirBaixa(conta: ContaPagar) {
    contaEmBaixa.value = conta;
    const saldo = Number(conta.valor) - Number(conta.valor_pago);
    formPagamento.reset();
    formPagamento.valor = saldo.toFixed(2);
    formPagamento.data = new Date().toISOString().slice(0, 10);
}

function registrarPagamento() {
    if (!contaEmBaixa.value) return;

    formPagamento.post(route('contas-pagar.pagamentos.store', contaEmBaixa.value.id), {
        preserveScroll: true,
        onSuccess: () => (contaEmBaixa.value = null),
    });
}
</script>

<template>
    <Head title="Contas a pagar" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Contas a pagar</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Fornecedores e despesas — recorrentes se renovam sozinhas ao quitar.</p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Nova conta
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
                        <th class="px-4 py-3">Descrição</th>
                        <th class="px-4 py-3">Vencimento</th>
                        <th class="px-4 py-3">Valor</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="conta in props.contas.data" :key="conta.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">
                                {{ conta.descricao }}
                                <span v-if="conta.recorrente" class="ml-1 text-[10px] text-sidebar-800/50">(recorrente)</span>
                            </p>
                            <p class="text-xs text-sidebar-800/60">
                                {{ conta.fornecedor }}<span v-if="conta.categoria"> · {{ conta.categoria }}</span>
                            </p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">{{ new Date(conta.data_vencimento).toLocaleDateString('pt-BR') }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ moeda(conta.valor) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[conta.status]">
                                {{ rotulosStatus[conta.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="conta.status !== 'paga'"
                                type="button"
                                class="mr-3 text-sm font-medium text-primary-600 hover:underline"
                                @click="abrirBaixa(conta)"
                            >
                                Pagar
                            </button>
                            <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="remover(conta)">
                                Remover
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.contas.data.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="5">
                            Nenhuma conta a pagar cadastrada.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.contas.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.contas.prev_page_url"
                @click="irPara(props.contas.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">{{ props.contas.current_page }} / {{ props.contas.last_page }}</span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.contas.next_page_url"
                @click="irPara(props.contas.next_page_url)"
            >
                Próxima
            </button>
        </div>

        <!-- Modal: nova conta -->
        <Modal :open="modalCriarAberto" titulo="Nova conta a pagar" @close="modalCriarAberto = false">
            <form class="space-y-3" @submit.prevent="salvar">
                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Descrição</label>
                    <input
                        v-model="form.descricao"
                        type="text"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                    <p v-if="form.errors.descricao" class="mt-1 text-xs text-red-600">{{ form.errors.descricao }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Fornecedor</label>
                        <input
                            v-model="form.fornecedor"
                            type="text"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Categoria</label>
                        <input
                            v-model="form.categoria"
                            type="text"
                            placeholder="Ex: Aluguel, Água/luz…"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Valor (R$)</label>
                        <input
                            v-model="form.valor"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="form.errors.valor" class="mt-1 text-xs text-red-600">{{ form.errors.valor }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Vencimento</label>
                        <input
                            v-model="form.data_vencimento"
                            type="date"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-sidebar-800">
                    <input v-model="form.recorrente" type="checkbox" class="rounded border-surface-200" />
                    Despesa recorrente (mensal) — ao quitar, gera a próxima automaticamente
                </label>

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
                        Salvar
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal: baixa -->
        <Modal :open="contaEmBaixa !== null" :titulo="contaEmBaixa ? 'Pagar: ' + contaEmBaixa.descricao : ''" @close="contaEmBaixa = null">
            <form v-if="contaEmBaixa" class="space-y-3" @submit.prevent="registrarPagamento">
                <p class="text-sm text-sidebar-800/70">
                    Saldo: <strong>{{ moeda(Number(contaEmBaixa.valor) - Number(contaEmBaixa.valor_pago)) }}</strong>
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <input
                        v-model="formPagamento.valor"
                        type="number"
                        min="0.01"
                        step="0.01"
                        required
                        class="rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                    <input
                        v-model="formPagamento.data"
                        type="date"
                        required
                        class="rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                </div>
                <p v-if="formPagamento.errors.valor" class="text-xs text-red-600">{{ formPagamento.errors.valor }}</p>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-surface-200 py-2.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                        @click="contaEmBaixa = null"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="formPagamento.processing"
                        class="flex-1 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                    >
                        Confirmar
                    </button>
                </div>
            </form>
        </Modal>
    </PainelLayout>
</template>
