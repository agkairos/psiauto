<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

interface Unidade {
    id: number;
    nome: string;
}

interface Marca {
    id: number;
    nome: string;
}

interface Modelo {
    id: number;
    nome: string;
}

interface Aplicacao {
    id: number;
    marca: Marca;
    modelo: Modelo | null;
    ano_inicio: number | null;
    ano_fim: number | null;
}

interface Produto {
    id: number;
    codigo: string | null;
    codigo_barras: string | null;
    nome: string;
    marca: string | null;
    unidade_medida: string;
    custo: string;
    preco_venda: string;
    estoque_minimo: number;
    visivel_para_cliente: boolean;
    ativo: boolean;
    saldo_unidade_atual: number;
    aplicacoes: Aplicacao[];
}

const props = defineProps<{
    produtos: Produto[];
    unidades: Unidade[];
    unidadeId: number | null;
    marcas: Marca[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

function trocarUnidade(unidadeId: string) {
    router.get(route('produtos.index'), { unidade_id: unidadeId }, { preserveState: true });
}

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Criar / editar produto
const modalAberto = ref(false);
const produtoEmEdicao = ref<Produto | null>(null);

const form = useForm({
    codigo: '',
    codigo_barras: '',
    nome: '',
    marca: '',
    unidade_medida: 'un',
    custo: '',
    preco_venda: '',
    estoque_minimo: 0,
    visivel_para_cliente: false,
    ativo: true,
});

function abrirCriacao() {
    produtoEmEdicao.value = null;
    form.reset();
    form.unidade_medida = 'un';
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEdicao(produto: Produto) {
    produtoEmEdicao.value = produto;
    form.codigo = produto.codigo ?? '';
    form.codigo_barras = produto.codigo_barras ?? '';
    form.nome = produto.nome;
    form.marca = produto.marca ?? '';
    form.unidade_medida = produto.unidade_medida;
    form.custo = produto.custo;
    form.preco_venda = produto.preco_venda;
    form.estoque_minimo = produto.estoque_minimo;
    form.visivel_para_cliente = produto.visivel_para_cliente;
    form.ativo = produto.ativo;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    produtoEmEdicao.value = null;
}

function salvar() {
    if (produtoEmEdicao.value) {
        form.put(route('produtos.update', produtoEmEdicao.value.id), { onSuccess: () => fecharModal() });
    } else {
        form.post(route('produtos.store'), { onSuccess: () => fecharModal() });
    }
}

function remover(produto: Produto) {
    if (!confirm(`Remover o produto "${produto.nome}"?`)) return;
    router.delete(route('produtos.destroy', produto.id), { preserveScroll: true });
}

function resincronizarProdutoEmEdicao() {
    if (!produtoEmEdicao.value) return;
    const atualizado = props.produtos.find((p) => p.id === produtoEmEdicao.value?.id);
    if (atualizado) produtoEmEdicao.value = atualizado;
}

// Movimentação de estoque
const formMovimentacao = useForm({
    unidade_id: props.unidadeId,
    tipo: 'entrada' as 'entrada' | 'saida' | 'ajuste' | 'perda',
    quantidade: 1,
    custo_unitario: '',
    motivo: '',
});

function registrarMovimentacao() {
    if (!produtoEmEdicao.value) return;

    formMovimentacao.post(route('produtos.movimentacoes.store', produtoEmEdicao.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            formMovimentacao.reset();
            formMovimentacao.unidade_id = props.unidadeId;
            formMovimentacao.tipo = 'entrada';
            formMovimentacao.quantidade = 1;
            resincronizarProdutoEmEdicao();
        },
    });
}

// Aplicação por veículo
const modelosDaMarca = ref<Modelo[]>([]);
const formAplicacao = useForm({
    marca_id: null as number | null,
    modelo_id: null as number | null,
    ano_inicio: '',
    ano_fim: '',
});

watch(
    () => formAplicacao.marca_id,
    async (marcaId) => {
        formAplicacao.modelo_id = null;
        modelosDaMarca.value = [];
        if (!marcaId) return;

        const resposta = await fetch(route('catalogo.modelos', marcaId), { headers: { Accept: 'application/json' } });
        modelosDaMarca.value = await resposta.json();
    },
);

function adicionarAplicacao() {
    if (!produtoEmEdicao.value) return;

    formAplicacao.post(route('produtos.aplicacoes.store', produtoEmEdicao.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            formAplicacao.reset();
            resincronizarProdutoEmEdicao();
        },
    });
}

function removerAplicacao(aplicacao: Aplicacao) {
    router.delete(route('aplicacoes-produto.destroy', aplicacao.id), {
        preserveScroll: true,
        onSuccess: () => resincronizarProdutoEmEdicao(),
    });
}
</script>

<template>
    <Head title="Produtos e estoque" />

    <PainelLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Produtos e estoque</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Catálogo de peças e saldo por unidade.</p>
            </div>

            <div class="flex items-center gap-2">
                <select
                    :value="props.unidadeId ?? ''"
                    class="rounded-lg border border-surface-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400"
                    @change="trocarUnidade(($event.target as HTMLSelectElement).value)"
                >
                    <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                </select>

                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                    @click="abrirCriacao"
                >
                    <PlusIcon class="h-4 w-4" />
                    Novo produto
                </button>
            </div>
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
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">Saldo</th>
                        <th class="px-4 py-3">Preço</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="produto in props.produtos" :key="produto.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ produto.nome }}</p>
                            <p class="text-xs text-sidebar-800/60">
                                {{ produto.marca }}<span v-if="produto.codigo"> · Cód. {{ produto.codigo }}</span>
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="
                                    produto.saldo_unidade_atual <= produto.estoque_minimo
                                        ? 'bg-red-50 text-red-700'
                                        : 'bg-primary-50 text-primary-700'
                                "
                            >
                                {{ produto.saldo_unidade_atual }} {{ produto.unidade_medida }}
                            </span>
                            <span v-if="produto.saldo_unidade_atual <= produto.estoque_minimo" class="ml-1 text-[10px] text-red-600">
                                abaixo do mínimo ({{ produto.estoque_minimo }})
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">{{ moeda(produto.preco_venda) }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="mr-3 text-sm font-medium text-primary-600 hover:underline" @click="abrirEdicao(produto)">
                                Editar
                            </button>
                            <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="remover(produto)">
                                Remover
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.produtos.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="4">
                            Nenhum produto cadastrado ainda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal: criar / editar -->
        <Modal :open="modalAberto" :titulo="produtoEmEdicao ? 'Editar produto' : 'Novo produto'" max-width="lg" @close="fecharModal">
            <form class="space-y-3" @submit.prevent="salvar">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome</label>
                        <input
                            v-model="form.nome"
                            type="text"
                            required
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="form.errors.nome" class="mt-1 text-xs text-red-600">{{ form.errors.nome }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Marca do produto</label>
                        <input
                            v-model="form.marca"
                            type="text"
                            placeholder="Ex: Bosch"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Código</label>
                        <input
                            v-model="form.codigo"
                            type="text"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="form.errors.codigo" class="mt-1 text-xs text-red-600">{{ form.errors.codigo }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Cód. de barras</label>
                        <input
                            v-model="form.codigo_barras"
                            type="text"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                        <select
                            v-model="form.unidade_medida"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        >
                            <option value="un">un</option>
                            <option value="litro">litro</option>
                            <option value="kg">kg</option>
                            <option value="par">par</option>
                            <option value="jogo">jogo</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Custo (R$)</label>
                        <input
                            v-model="form.custo"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Preço de venda (R$)</label>
                        <input
                            v-model="form.preco_venda"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Estoque mínimo</label>
                        <input
                            v-model="form.estoque_minimo"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-sidebar-800">
                    <input v-model="form.visivel_para_cliente" type="checkbox" class="rounded border-surface-200" />
                    Visível na consulta do cliente (só disponibilidade — sem venda pela internet)
                </label>

                <label v-if="produtoEmEdicao" class="flex items-center gap-2 text-sm text-sidebar-800">
                    <input v-model="form.ativo" type="checkbox" class="rounded border-surface-200" />
                    Produto ativo
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

            <!-- Movimentações e aplicações: só depois que o produto já existe -->
            <div v-if="produtoEmEdicao" class="mt-6 space-y-6 border-t border-surface-200 pt-5">
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-sidebar-900">Movimentar estoque</h3>
                    <form class="grid grid-cols-2 gap-2 rounded-lg border border-surface-200 p-3" @submit.prevent="registrarMovimentacao">
                        <select
                            v-model="formMovimentacao.unidade_id"
                            required
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        >
                            <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                        </select>
                        <select
                            v-model="formMovimentacao.tipo"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        >
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                            <option value="ajuste">Ajuste</option>
                            <option value="perda">Perda</option>
                        </select>
                        <input
                            v-model="formMovimentacao.quantidade"
                            type="number"
                            min="1"
                            required
                            placeholder="Quantidade"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-if="formMovimentacao.tipo === 'entrada'"
                            v-model="formMovimentacao.custo_unitario"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Custo unitário (R$)"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formMovimentacao.motivo"
                            type="text"
                            placeholder="Motivo (opcional)"
                            class="col-span-2 rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <p v-if="formMovimentacao.errors.quantidade" class="col-span-2 text-xs text-red-600">
                            {{ formMovimentacao.errors.quantidade }}
                        </p>
                        <button
                            type="submit"
                            :disabled="formMovimentacao.processing"
                            class="col-span-2 rounded-lg border border-surface-200 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                        >
                            Registrar movimentação
                        </button>
                    </form>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-sidebar-900">Aplicação por veículo</h3>

                    <ul class="mb-2 flex flex-wrap gap-1">
                        <li
                            v-for="aplicacao in produtoEmEdicao.aplicacoes"
                            :key="aplicacao.id"
                            class="flex items-center gap-1 rounded-full bg-surface-100 px-2 py-1 text-xs text-sidebar-800"
                        >
                            {{ aplicacao.marca.nome }}<span v-if="aplicacao.modelo"> {{ aplicacao.modelo.nome }}</span>
                            <span v-if="aplicacao.ano_inicio"> ({{ aplicacao.ano_inicio }}-{{ aplicacao.ano_fim || '' }})</span>
                            <button type="button" class="text-red-600" @click="removerAplicacao(aplicacao)">×</button>
                        </li>
                        <li v-if="produtoEmEdicao.aplicacoes.length === 0" class="text-xs text-sidebar-800/60">
                            Serve pra qualquer veículo (nenhuma aplicação restringida ainda).
                        </li>
                    </ul>

                    <form class="grid grid-cols-2 gap-2 rounded-lg border border-surface-200 p-3" @submit.prevent="adicionarAplicacao">
                        <select
                            v-model="formAplicacao.marca_id"
                            required
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        >
                            <option :value="null" disabled>Marca</option>
                            <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nome }}</option>
                        </select>
                        <select
                            v-model="formAplicacao.modelo_id"
                            :disabled="!formAplicacao.marca_id"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400 disabled:bg-surface-100"
                        >
                            <option :value="null">Modelo (todos)</option>
                            <option v-for="m in modelosDaMarca" :key="m.id" :value="m.id">{{ m.nome }}</option>
                        </select>
                        <input
                            v-model="formAplicacao.ano_inicio"
                            type="number"
                            placeholder="Ano início"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formAplicacao.ano_fim"
                            type="number"
                            placeholder="Ano fim"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <button
                            type="submit"
                            :disabled="formAplicacao.processing"
                            class="col-span-2 rounded-lg border border-surface-200 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                        >
                            Adicionar aplicação
                        </button>
                    </form>
                </div>
            </div>
        </Modal>
    </PainelLayout>
</template>
