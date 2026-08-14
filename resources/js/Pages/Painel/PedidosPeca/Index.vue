<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

interface Produto {
    id: number;
    nome: string;
}

interface Veiculo {
    id: number;
    placa: string;
}

interface Cliente {
    id: number;
    nome: string;
    veiculos: Veiculo[];
}

interface Unidade {
    id: number;
    nome: string;
}

interface ItemPedido {
    id: number;
    descricao: string;
    quantidade: number;
    disponibilidade: 'em_estoque' | 'sob_encomenda' | 'indisponivel' | null;
    preco_unitario: string | null;
    prazo_entrega_dias: number | null;
    produto: Produto | null;
}

interface Pedido {
    id: number;
    status: 'solicitado' | 'orcado' | 'reservado' | 'retirado' | 'cancelado' | 'expirado';
    validade_orcamento: string | null;
    reservado_ate: string | null;
    observacoes: string | null;
    cliente: { id: number; nome: string };
    veiculo: { id: number; placa: string } | null;
    unidade: { id: number; nome: string };
    itens: ItemPedido[];
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    pedidos: Paginado<Pedido>;
    unidades: Unidade[];
    clientes: Cliente[];
    produtos: Produto[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const rotulosStatus: Record<string, string> = {
    solicitado: 'Aguardando orçamento',
    orcado: 'Orçamento pronto',
    reservado: 'Reservado',
    retirado: 'Retirado',
    cancelado: 'Cancelado',
    expirado: 'Reserva expirada',
};

const coresStatus: Record<string, string> = {
    solicitado: 'bg-surface-200 text-sidebar-800',
    orcado: 'bg-amber-50 text-amber-700',
    reservado: 'bg-blue-50 text-blue-700',
    retirado: 'bg-green-50 text-green-700',
    cancelado: 'bg-red-50 text-red-700',
    expirado: 'bg-red-50 text-red-700',
};

const rotulosDisponibilidade: Record<string, string> = {
    em_estoque: 'Em estoque',
    sob_encomenda: 'Sob encomenda',
    indisponivel: 'Indisponível',
};

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function data(valor: string | null): string {
    return valor ? new Date(valor).toLocaleDateString('pt-BR') : '—';
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

// Criar pedido
const modalCriarAberto = ref(false);
const form = useForm({
    unidade_id: null as number | null,
    cliente_id: null as number | null,
    veiculo_id: null as number | null,
    observacoes: '',
    itens: [{ descricao: '', produto_id: null as number | null, quantidade: 1 }],
});

const veiculosDoCliente = computed(() => {
    const cliente = props.clientes.find((c) => c.id === form.cliente_id);
    return cliente?.veiculos ?? [];
});

function abrirCriacao() {
    form.reset();
    form.itens = [{ descricao: '', produto_id: null, quantidade: 1 }];
    modalCriarAberto.value = true;
}

function adicionarItem() {
    form.itens.push({ descricao: '', produto_id: null, quantidade: 1 });
}

function removerItem(index: number) {
    form.itens.splice(index, 1);
}

function salvar() {
    form.post(route('pedidos-peca.store'), { onSuccess: () => (modalCriarAberto.value = false) });
}

// Precificação inline por item
const precificacoes = reactive<Record<number, { disponibilidade: string; preco_unitario: string; prazo_entrega_dias: string }>>({});

function iniciarPrecificacao(item: ItemPedido) {
    precificacoes[item.id] = {
        disponibilidade: item.disponibilidade ?? 'em_estoque',
        preco_unitario: item.preco_unitario ?? '',
        prazo_entrega_dias: item.prazo_entrega_dias?.toString() ?? '',
    };
}

function salvarPrecificacao(item: ItemPedido) {
    const dados = precificacoes[item.id];
    router.put(
        route('pedidos-peca.itens.precificar', item.id),
        {
            disponibilidade: dados.disponibilidade,
            preco_unitario: dados.disponibilidade === 'indisponivel' ? null : dados.preco_unitario,
            prazo_entrega_dias: dados.disponibilidade === 'sob_encomenda' ? dados.prazo_entrega_dias : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => delete precificacoes[item.id],
        },
    );
}

// Ações do pedido
function reservar(pedido: Pedido) {
    if (!confirm('Reservar as peças em estoque deste pedido para retirada?')) return;
    router.post(route('pedidos-peca.reservar', pedido.id), {}, { preserveScroll: true });
}

function retirar(pedido: Pedido) {
    if (!confirm('Confirmar que o cliente retirou as peças?')) return;
    router.post(route('pedidos-peca.retirar', pedido.id), {}, { preserveScroll: true });
}

function cancelar(pedido: Pedido) {
    if (!confirm('Cancelar este pedido de peça?')) return;
    router.post(route('pedidos-peca.cancelar', pedido.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Pedidos de peça" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Pedidos de peça</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">
                    Consulta de disponibilidade, orçamento item a item e reserva para retirada presencial — sem venda pela internet.
                </p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Novo pedido
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="mt-6 space-y-4">
            <div v-for="pedido in props.pedidos.data" :key="pedido.id" class="rounded-xl border border-surface-200 bg-white p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-sidebar-900">
                            Pedido #{{ pedido.id }} — {{ pedido.cliente.nome }}
                            <span v-if="pedido.veiculo" class="text-sidebar-800/60">({{ pedido.veiculo.placa }})</span>
                        </p>
                        <p class="text-xs text-sidebar-800/60">
                            {{ pedido.unidade.nome }}
                            <span v-if="pedido.status === 'orcado'"> · válido até {{ data(pedido.validade_orcamento) }}</span>
                            <span v-if="pedido.status === 'reservado'"> · retirar até {{ data(pedido.reservado_ate) }}</span>
                        </p>
                    </div>
                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[pedido.status]">
                        {{ rotulosStatus[pedido.status] }}
                    </span>
                </div>

                <table class="mt-3 w-full text-left text-sm">
                    <thead class="border-b border-surface-100 text-xs uppercase text-sidebar-800/60">
                        <tr>
                            <th class="py-2">Item</th>
                            <th class="py-2">Qtd</th>
                            <th class="py-2">Disponibilidade</th>
                            <th class="py-2">Preço</th>
                            <th class="py-2 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in pedido.itens" :key="item.id" class="border-b border-surface-50 last:border-0">
                            <td class="py-2 text-sidebar-900">{{ item.descricao }}</td>
                            <td class="py-2 text-sidebar-800">{{ item.quantidade }}</td>

                            <template v-if="precificacoes[item.id]">
                                <td class="py-2" colspan="3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <select v-model="precificacoes[item.id].disponibilidade" class="rounded-lg border border-surface-200 px-2 py-1 text-xs">
                                            <option value="em_estoque">Em estoque</option>
                                            <option value="sob_encomenda">Sob encomenda</option>
                                            <option value="indisponivel">Indisponível</option>
                                        </select>
                                        <input
                                            v-if="precificacoes[item.id].disponibilidade !== 'indisponivel'"
                                            v-model="precificacoes[item.id].preco_unitario"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="Preço"
                                            class="w-24 rounded-lg border border-surface-200 px-2 py-1 text-xs"
                                        />
                                        <input
                                            v-if="precificacoes[item.id].disponibilidade === 'sob_encomenda'"
                                            v-model="precificacoes[item.id].prazo_entrega_dias"
                                            type="number"
                                            min="1"
                                            placeholder="Prazo (dias)"
                                            class="w-28 rounded-lg border border-surface-200 px-2 py-1 text-xs"
                                        />
                                        <button type="button" class="text-xs font-medium text-primary-600 hover:underline" @click="salvarPrecificacao(item)">
                                            Salvar
                                        </button>
                                        <button type="button" class="text-xs text-sidebar-800/60 hover:underline" @click="delete precificacoes[item.id]">
                                            Cancelar
                                        </button>
                                    </div>
                                </td>
                            </template>
                            <template v-else>
                                <td class="py-2 text-sidebar-800">
                                    <span v-if="item.disponibilidade">{{ rotulosDisponibilidade[item.disponibilidade] }}</span>
                                    <span v-else class="text-sidebar-800/40">—</span>
                                </td>
                                <td class="py-2 text-sidebar-800">
                                    {{ item.preco_unitario ? moeda(Number(item.preco_unitario) * item.quantidade) : '—' }}
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        v-if="pedido.status === 'solicitado' || pedido.status === 'orcado'"
                                        type="button"
                                        class="text-xs font-medium text-primary-600 hover:underline"
                                        @click="iniciarPrecificacao(item)"
                                    >
                                        Precificar
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3 flex justify-end gap-3">
                    <button
                        v-if="pedido.status === 'orcado'"
                        type="button"
                        class="rounded-lg bg-primary-400 px-3 py-1.5 text-xs font-semibold text-sidebar-950 hover:opacity-90"
                        @click="reservar(pedido)"
                    >
                        Reservar para retirada
                    </button>
                    <button
                        v-if="pedido.status === 'reservado'"
                        type="button"
                        class="rounded-lg bg-primary-400 px-3 py-1.5 text-xs font-semibold text-sidebar-950 hover:opacity-90"
                        @click="retirar(pedido)"
                    >
                        Confirmar retirada
                    </button>
                    <button
                        v-if="!['retirado', 'cancelado', 'expirado'].includes(pedido.status)"
                        type="button"
                        class="rounded-lg border border-surface-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
                        @click="cancelar(pedido)"
                    >
                        Cancelar pedido
                    </button>
                </div>
            </div>

            <p v-if="props.pedidos.data.length === 0" class="py-10 text-center text-sm text-sidebar-800/60">
                Nenhum pedido de peça registrado ainda.
            </p>
        </div>

        <div v-if="props.pedidos.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.pedidos.prev_page_url"
                @click="irPara(props.pedidos.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">{{ props.pedidos.current_page }} / {{ props.pedidos.last_page }}</span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.pedidos.next_page_url"
                @click="irPara(props.pedidos.next_page_url)"
            >
                Próxima
            </button>
        </div>

        <!-- Modal: novo pedido -->
        <Modal :open="modalCriarAberto" titulo="Novo pedido de peça" @close="modalCriarAberto = false">
            <form class="space-y-3" @submit.prevent="salvar">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                        <select
                            v-model="form.unidade_id"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        >
                            <option :value="null" disabled>Selecione…</option>
                            <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Cliente</label>
                        <select
                            v-model="form.cliente_id"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                            @change="form.veiculo_id = null"
                        >
                            <option :value="null" disabled>Selecione…</option>
                            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Veículo (opcional)</label>
                    <select
                        v-model="form.veiculo_id"
                        :disabled="!form.cliente_id"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 disabled:bg-surface-100"
                    >
                        <option :value="null">Nenhum</option>
                        <option v-for="v in veiculosDoCliente" :key="v.id" :value="v.id">{{ v.placa }}</option>
                    </select>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="block text-sm font-medium text-sidebar-900">Itens</label>
                        <button type="button" class="text-xs font-medium text-primary-600 hover:underline" @click="adicionarItem">
                            + Adicionar item
                        </button>
                    </div>

                    <div v-for="(item, index) in form.itens" :key="index" class="mb-2 flex items-center gap-2">
                        <input
                            v-model="item.descricao"
                            type="text"
                            placeholder="Descrição da peça"
                            required
                            class="flex-1 rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <select v-model="item.produto_id" class="w-40 rounded-lg border border-surface-200 px-2 py-2 text-xs">
                            <option :value="null">Fora do catálogo</option>
                            <option v-for="p in produtos" :key="p.id" :value="p.id">{{ p.nome }}</option>
                        </select>
                        <input
                            v-model.number="item.quantidade"
                            type="number"
                            min="1"
                            class="w-16 rounded-lg border border-surface-200 px-2 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <button
                            v-if="form.itens.length > 1"
                            type="button"
                            class="text-red-600 hover:text-red-800"
                            @click="removerItem(index)"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Observações</label>
                    <textarea
                        v-model="form.observacoes"
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
                        Salvar
                    </button>
                </div>
            </form>
        </Modal>
    </PainelLayout>
</template>
