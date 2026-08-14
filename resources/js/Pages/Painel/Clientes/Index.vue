<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { MASCARA_CPF_CNPJ, MASCARA_TELEFONE } from '@/lib/masks';

interface MarcaOuModelo {
    id: number;
    nome: string;
}

interface Veiculo {
    id: number;
    placa: string;
    ano_fabricacao: number | null;
    ano_modelo: number | null;
    cor: string | null;
    quilometragem_atual: number | null;
    marca: MarcaOuModelo;
    modelo: MarcaOuModelo;
}

interface Cliente {
    id: number;
    nome: string;
    telefone: string | null;
    email: string | null;
    cpf_cnpj: string | null;
    observacoes_internas: string | null;
    ativo: boolean;
    veiculos: Veiculo[];
    veiculos_count: number;
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    clientes: Paginado<Cliente>;
    busca: string;
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

// Busca com debounce, refletindo na URL (?busca=...)
const termoBusca = ref(props.busca);
let debounce: ReturnType<typeof setTimeout> | undefined;
watch(termoBusca, (valor) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('clientes.index'), { busca: valor || undefined }, { preserveState: true, replace: true });
    }, 350);
});

const modalAberto = ref(false);
const clienteEmEdicao = ref<Cliente | null>(null);

const form = useForm({
    nome: '',
    telefone: '',
    email: '',
    cpf_cnpj: '',
    observacoes_internas: '',
    ativo: true,
});

function abrirCriacao() {
    clienteEmEdicao.value = null;
    form.reset();
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEdicao(cliente: Cliente) {
    clienteEmEdicao.value = cliente;
    form.nome = cliente.nome;
    form.telefone = cliente.telefone ?? '';
    form.email = cliente.email ?? '';
    form.cpf_cnpj = cliente.cpf_cnpj ?? '';
    form.observacoes_internas = cliente.observacoes_internas ?? '';
    form.ativo = cliente.ativo;
    formVeiculo.reset();
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    clienteEmEdicao.value = null;
}

function salvar() {
    if (clienteEmEdicao.value) {
        form.put(route('clientes.update', clienteEmEdicao.value.id), {
            onSuccess: () => fecharModal(),
        });
    } else {
        form.post(route('clientes.store'), {
            onSuccess: () => fecharModal(),
        });
    }
}

function remover(cliente: Cliente) {
    if (!confirm(`Remover o cliente "${cliente.nome}"? Isso não pode ser desfeito.`)) {
        return;
    }

    router.delete(route('clientes.destroy', cliente.id), { preserveScroll: true });
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

// Veículos (só existem depois que o cliente já foi criado)
const marcas = ref<MarcaOuModelo[]>([]);
const modelos = ref<MarcaOuModelo[]>([]);
const carregandoModelos = ref(false);

async function carregarMarcas() {
    if (marcas.value.length > 0) return;

    const resposta = await fetch(route('catalogo.marcas'), { headers: { Accept: 'application/json' } });
    marcas.value = await resposta.json();
}

const formVeiculo = useForm({
    marca_id: null as number | null,
    modelo_id: null as number | null,
    placa: '',
    ano_fabricacao: '',
    ano_modelo: '',
    cor: '',
    quilometragem_atual: '',
});

watch(
    () => formVeiculo.marca_id,
    async (marcaId) => {
        formVeiculo.modelo_id = null;
        modelos.value = [];
        if (!marcaId) return;

        carregandoModelos.value = true;
        try {
            const resposta = await fetch(route('catalogo.modelos', marcaId), {
                headers: { Accept: 'application/json' },
            });
            modelos.value = await resposta.json();
        } finally {
            carregandoModelos.value = false;
        }
    },
);

function resincronizarClienteEmEdicao() {
    if (!clienteEmEdicao.value) return;

    const atualizado = props.clientes.data.find((c) => c.id === clienteEmEdicao.value?.id);
    if (atualizado) clienteEmEdicao.value = atualizado;
}

function adicionarVeiculo() {
    if (!clienteEmEdicao.value) return;

    formVeiculo.post(route('clientes.veiculos.store', clienteEmEdicao.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            formVeiculo.reset();
            resincronizarClienteEmEdicao();
        },
    });
}

function removerVeiculo(veiculo: Veiculo) {
    if (!confirm(`Remover o veículo placa ${veiculo.placa}?`)) return;

    router.delete(route('veiculos.destroy', veiculo.id), {
        preserveScroll: true,
        onSuccess: () => resincronizarClienteEmEdicao(),
    });
}
</script>

<template>
    <Head title="Clientes" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Clientes</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Base de clientes e veículos da empresa.</p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Novo cliente
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="relative mt-6 max-w-sm">
            <MagnifyingGlassIcon
                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-sidebar-800/40"
            />
            <input
                v-model="termoBusca"
                type="search"
                placeholder="Buscar por nome, telefone ou placa…"
                class="w-full rounded-lg border border-surface-200 bg-white py-2 pr-3 pl-9 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
            />
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-surface-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-xs uppercase text-sidebar-800/60">
                    <tr>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Contato</th>
                        <th class="px-4 py-3">Veículos</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cliente in props.clientes.data" :key="cliente.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ cliente.nome }}</p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">
                            <p>{{ cliente.telefone ?? '—' }}</p>
                            <p v-if="cliente.email" class="text-xs text-sidebar-800/60">{{ cliente.email }}</p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">
                            <span v-if="cliente.veiculos_count === 0">—</span>
                            <span v-else>
                                {{ cliente.veiculos.map((v) => `${v.marca.nome} ${v.modelo.nome} (${v.placa})`).join(', ') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="cliente.ativo ? 'bg-primary-50 text-primary-700' : 'bg-surface-200 text-sidebar-800/60'"
                            >
                                {{ cliente.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="mr-3 text-sm font-medium text-primary-600 hover:underline" @click="abrirEdicao(cliente)">
                                Editar
                            </button>
                            <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="remover(cliente)">
                                Remover
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.clientes.data.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="5">
                            Nenhum cliente encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.clientes.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.clientes.prev_page_url"
                @click="irPara(props.clientes.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">{{ props.clientes.current_page }} / {{ props.clientes.last_page }}</span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.clientes.next_page_url"
                @click="irPara(props.clientes.next_page_url)"
            >
                Próxima
            </button>
        </div>

        <!-- Modal: criar / editar -->
        <Modal
            :open="modalAberto"
            :titulo="clienteEmEdicao ? 'Editar cliente' : 'Novo cliente'"
            max-width="lg"
            @close="fecharModal"
        >
            <form class="space-y-4" @submit.prevent="salvar">
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">Telefone</label>
                        <input
                            v-model="form.telefone"
                            v-maska="{ mask: MASCARA_TELEFONE }"
                            type="text"
                            placeholder="(11) 99999-9999"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                        />
                        <p v-if="form.errors.telefone" class="mt-1 text-xs text-red-600">{{ form.errors.telefone }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-sidebar-900">CPF/CNPJ</label>
                        <input
                            v-model="form.cpf_cnpj"
                            v-maska="{ mask: MASCARA_CPF_CNPJ }"
                            type="text"
                            class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                        />
                        <p v-if="form.errors.cpf_cnpj" class="mt-1 text-xs text-red-600">{{ form.errors.cpf_cnpj }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">E-mail</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Observações internas</label>
                    <textarea
                        v-model="form.observacoes_internas"
                        rows="2"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                </div>

                <label v-if="clienteEmEdicao" class="flex items-center gap-2 text-sm text-sidebar-800/80">
                    <input v-model="form.ativo" type="checkbox" class="rounded border-surface-200" />
                    Cliente ativo
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

            <!-- Veículos: só depois que o cliente já existe -->
            <div v-if="clienteEmEdicao" class="mt-6 border-t border-surface-200 pt-5">
                <h3 class="mb-2 text-sm font-semibold text-sidebar-900">Veículos</h3>

                <ul class="mb-3 space-y-1">
                    <li
                        v-for="veiculo in clienteEmEdicao.veiculos"
                        :key="veiculo.id"
                        class="flex items-center justify-between rounded-lg bg-surface-50 px-3 py-2 text-sm"
                    >
                        <span class="text-sidebar-800">
                            {{ veiculo.marca.nome }} {{ veiculo.modelo.nome }} — {{ veiculo.placa }}
                            <span v-if="veiculo.ano_modelo" class="text-sidebar-800/60"> ({{ veiculo.ano_modelo }})</span>
                        </span>
                        <button type="button" class="text-xs font-medium text-red-600 hover:underline" @click="removerVeiculo(veiculo)">
                            Remover
                        </button>
                    </li>
                    <li v-if="clienteEmEdicao.veiculos.length === 0" class="text-xs text-sidebar-800/60">
                        Nenhum veículo cadastrado.
                    </li>
                </ul>

                <form class="space-y-2" @submit.prevent="adicionarVeiculo">
                    <div class="grid grid-cols-2 gap-2">
                        <select
                            v-model="formVeiculo.marca_id"
                            required
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            @focus="carregarMarcas"
                        >
                            <option :value="null" disabled>Marca</option>
                            <option v-for="marca in marcas" :key="marca.id" :value="marca.id">{{ marca.nome }}</option>
                        </select>

                        <select
                            v-model="formVeiculo.modelo_id"
                            required
                            :disabled="!formVeiculo.marca_id || carregandoModelos"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400 disabled:bg-surface-100"
                        >
                            <option :value="null" disabled>
                                {{ carregandoModelos ? 'Carregando…' : 'Modelo' }}
                            </option>
                            <option v-for="modelo in modelos" :key="modelo.id" :value="modelo.id">{{ modelo.nome }}</option>
                        </select>
                    </div>

                    <p v-if="formVeiculo.errors.marca_id || formVeiculo.errors.modelo_id" class="text-xs text-red-600">
                        {{ formVeiculo.errors.marca_id || formVeiculo.errors.modelo_id }}
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        <input
                            v-model="formVeiculo.placa"
                            type="text"
                            required
                            placeholder="Placa"
                            maxlength="8"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm uppercase outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formVeiculo.cor"
                            type="text"
                            placeholder="Cor (opcional)"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                    </div>
                    <p v-if="formVeiculo.errors.placa" class="text-xs text-red-600">{{ formVeiculo.errors.placa }}</p>

                    <div class="grid grid-cols-3 gap-2">
                        <input
                            v-model="formVeiculo.ano_fabricacao"
                            type="number"
                            placeholder="Ano fab."
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formVeiculo.ano_modelo"
                            type="number"
                            placeholder="Ano modelo"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formVeiculo.quilometragem_atual"
                            type="number"
                            placeholder="KM atual"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="formVeiculo.processing"
                        class="w-full rounded-lg border border-surface-200 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                    >
                        Adicionar veículo
                    </button>
                </form>
            </div>
        </Modal>
    </PainelLayout>
</template>
