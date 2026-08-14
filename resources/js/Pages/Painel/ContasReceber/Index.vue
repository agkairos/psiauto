<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Cog6ToothIcon, PlusIcon } from '@heroicons/vue/24/outline';

interface FormaPagamento {
    id: number;
    nome: string;
    taxa_percentual: string;
    prazo_recebimento_dias: number;
    ativa: boolean;
}

interface Recebimento {
    id: number;
    valor: string;
    data: string;
    forma_pagamento: FormaPagamento | null;
}

interface Parcela {
    id: number;
    numero: number;
    valor: string;
    valor_recebido: string;
    data_vencimento: string;
    status: 'pendente' | 'parcial' | 'pago' | 'atrasado';
    recebimentos: Recebimento[];
}

interface ContaReceber {
    id: number;
    valor_total: string;
    valor_recebido: string;
    status: 'pendente' | 'parcial' | 'pago' | 'atrasado';
    created_at: string;
    cliente: { id: number; nome: string };
    unidade: { id: number; nome: string };
    forma_pagamento: FormaPagamento | null;
    ordem_servico: { id: number; veiculo: { id: number; placa: string } };
    parcelas: Parcela[];
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    contas: Paginado<ContaReceber>;
    formasPagamento: FormaPagamento[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const rotulosStatus: Record<string, string> = {
    pendente: 'Pendente',
    parcial: 'Parcial',
    pago: 'Pago',
    atrasado: 'Atrasado',
};

const coresStatus: Record<string, string> = {
    pendente: 'bg-surface-200 text-sidebar-800',
    parcial: 'bg-amber-50 text-amber-700',
    pago: 'bg-green-50 text-green-700',
    atrasado: 'bg-red-50 text-red-700',
};

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

// Detalhe da conta + baixa de recebimento
const contaSelecionada = ref<ContaReceber | null>(null);

function resincronizarContaSelecionada() {
    if (!contaSelecionada.value) return;
    const atualizada = props.contas.data.find((c) => c.id === contaSelecionada.value?.id);
    if (atualizada) contaSelecionada.value = atualizada;
}

function abrirDetalhe(conta: ContaReceber) {
    contaSelecionada.value = conta;
}

const formRecebimento = useForm({
    valor: '',
    data: new Date().toISOString().slice(0, 10),
    forma_pagamento_id: null as number | null,
});

const parcelaEmBaixa = ref<Parcela | null>(null);

function abrirBaixa(parcela: Parcela) {
    parcelaEmBaixa.value = parcela;
    const saldo = Number(parcela.valor) - Number(parcela.valor_recebido);
    formRecebimento.reset();
    formRecebimento.valor = saldo.toFixed(2);
    formRecebimento.data = new Date().toISOString().slice(0, 10);
}

function registrarRecebimento() {
    if (!parcelaEmBaixa.value) return;

    formRecebimento.post(route('financeiro.parcelas.recebimentos.store', parcelaEmBaixa.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            parcelaEmBaixa.value = null;
            resincronizarContaSelecionada();
        },
    });
}

// Formas de pagamento
const modalFormasPagamentoAberto = ref(false);
const formFormaPagamento = useForm({
    nome: '',
    taxa_percentual: '',
    prazo_recebimento_dias: 0,
    ativa: true,
});

function adicionarFormaPagamento() {
    formFormaPagamento.post(route('financeiro.formas-pagamento.store'), {
        preserveScroll: true,
        onSuccess: () => formFormaPagamento.reset(),
    });
}

function removerFormaPagamento(forma: FormaPagamento) {
    router.delete(route('financeiro.formas-pagamento.destroy', forma.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Financeiro" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Financeiro</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Contas a receber, geradas automaticamente na entrega da OS.</p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg border border-surface-200 px-4 py-2 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                @click="modalFormasPagamentoAberto = true"
            >
                <Cog6ToothIcon class="h-4 w-4" />
                Formas de pagamento
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
                        <th class="px-4 py-3">Cliente / Veículo</th>
                        <th class="px-4 py-3">Unidade</th>
                        <th class="px-4 py-3">Valor</th>
                        <th class="px-4 py-3">Recebido</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="conta in props.contas.data" :key="conta.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ conta.cliente.nome }}</p>
                            <p class="text-xs text-sidebar-800/60">{{ conta.ordem_servico.veiculo.placa }}</p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">{{ conta.unidade.nome }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ moeda(conta.valor_total) }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ moeda(conta.valor_recebido) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[conta.status]">
                                {{ rotulosStatus[conta.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="text-sm font-medium text-primary-600 hover:underline" @click="abrirDetalhe(conta)">
                                Ver / baixar
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.contas.data.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="6">
                            Nenhuma conta a receber ainda — nasce automaticamente quando uma OS é entregue.
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

        <!-- Modal: detalhe da conta + parcelas + baixa -->
        <Modal
            :open="contaSelecionada !== null"
            :titulo="contaSelecionada ? 'Conta de ' + contaSelecionada.cliente.nome : ''"
            max-width="lg"
            @close="contaSelecionada = null"
        >
            <div v-if="contaSelecionada" class="space-y-4">
                <div class="flex items-center justify-between rounded-lg bg-surface-50 p-3 text-sm">
                    <span>Total: <strong>{{ moeda(contaSelecionada.valor_total) }}</strong></span>
                    <span>Recebido: <strong>{{ moeda(contaSelecionada.valor_recebido) }}</strong></span>
                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[contaSelecionada.status]">
                        {{ rotulosStatus[contaSelecionada.status] }}
                    </span>
                </div>

                <div v-for="parcela in contaSelecionada.parcelas" :key="parcela.id" class="rounded-lg border border-surface-200 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-sidebar-900">
                            Parcela {{ parcela.numero }} — vence {{ new Date(parcela.data_vencimento).toLocaleDateString('pt-BR') }}
                        </p>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="coresStatus[parcela.status]">
                            {{ rotulosStatus[parcela.status] }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-sidebar-800/60">
                        {{ moeda(parcela.valor_recebido) }} de {{ moeda(parcela.valor) }}
                    </p>

                    <ul v-if="parcela.recebimentos.length > 0" class="mt-2 space-y-1">
                        <li v-for="r in parcela.recebimentos" :key="r.id" class="text-[11px] text-sidebar-800/60">
                            {{ moeda(r.valor) }} em {{ new Date(r.data).toLocaleDateString('pt-BR') }}
                            <span v-if="r.forma_pagamento"> — {{ r.forma_pagamento.nome }}</span>
                        </li>
                    </ul>

                    <template v-if="parcela.status !== 'pago'">
                        <button
                            v-if="parcelaEmBaixa?.id !== parcela.id"
                            type="button"
                            class="mt-2 rounded-md bg-primary-400 px-2.5 py-1 text-xs font-semibold text-sidebar-950 hover:opacity-90"
                            @click="abrirBaixa(parcela)"
                        >
                            Registrar recebimento
                        </button>

                        <form v-else class="mt-2 space-y-2" @submit.prevent="registrarRecebimento">
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    v-model="formRecebimento.valor"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                                />
                                <input
                                    v-model="formRecebimento.data"
                                    type="date"
                                    required
                                    class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                                />
                            </div>
                            <select
                                v-model="formRecebimento.forma_pagamento_id"
                                class="w-full rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            >
                                <option :value="null">Forma de pagamento (opcional)</option>
                                <option v-for="f in formasPagamento" :key="f.id" :value="f.id">{{ f.nome }}</option>
                            </select>
                            <p v-if="formRecebimento.errors.valor" class="text-xs text-red-600">{{ formRecebimento.errors.valor }}</p>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border border-surface-200 py-1.5 text-xs font-medium text-sidebar-800 hover:bg-surface-50"
                                    @click="parcelaEmBaixa = null"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="formRecebimento.processing"
                                    class="flex-1 rounded-lg bg-primary-400 py-1.5 text-xs font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                                >
                                    Confirmar
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </Modal>

        <!-- Modal: formas de pagamento -->
        <Modal :open="modalFormasPagamentoAberto" titulo="Formas de pagamento" @close="modalFormasPagamentoAberto = false">
            <ul class="mb-4 space-y-2">
                <li
                    v-for="forma in formasPagamento"
                    :key="forma.id"
                    class="flex items-center justify-between rounded-lg border border-surface-200 px-3 py-2 text-sm"
                >
                    <span>
                        {{ forma.nome }}
                        <span class="text-xs text-sidebar-800/60">
                            — taxa {{ forma.taxa_percentual }}% · recebe em {{ forma.prazo_recebimento_dias }}d
                        </span>
                    </span>
                    <button type="button" class="text-xs text-red-600 hover:underline" @click="removerFormaPagamento(forma)">
                        Desativar
                    </button>
                </li>
                <li v-if="formasPagamento.length === 0" class="text-xs text-sidebar-800/60">
                    Nenhuma forma de pagamento cadastrada.
                </li>
            </ul>

            <form class="space-y-2 rounded-lg border border-surface-200 p-3" @submit.prevent="adicionarFormaPagamento">
                <input
                    v-model="formFormaPagamento.nome"
                    type="text"
                    required
                    placeholder="Ex: Pix, Cartão de crédito…"
                    class="w-full rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                />
                <div class="grid grid-cols-2 gap-2">
                    <input
                        v-model="formFormaPagamento.taxa_percentual"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="Taxa %"
                        class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                    />
                    <input
                        v-model="formFormaPagamento.prazo_recebimento_dias"
                        type="number"
                        min="0"
                        placeholder="Prazo (dias)"
                        class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                    />
                </div>
                <button
                    type="submit"
                    :disabled="formFormaPagamento.processing"
                    class="w-full rounded-lg bg-primary-400 py-1.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                >
                    <PlusIcon class="mr-1 inline h-4 w-4" />
                    Adicionar
                </button>
            </form>
        </Modal>
    </PainelLayout>
</template>
