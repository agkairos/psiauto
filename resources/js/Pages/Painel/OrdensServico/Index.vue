<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import ClienteBusca from '@/Components/ClienteBusca.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

interface Unidade {
    id: number;
    nome: string;
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

interface AgendamentoRecebido {
    id: number;
    unidade_id: number;
    cliente_id: number;
    veiculo_id: number;
    hora_inicio: string;
    cliente: { id: number; nome: string };
    veiculo: { id: number; placa: string };
}

interface Checklist {
    km_entrada: number | null;
    nivel_combustivel: string | null;
    avarias: string[];
    objetos_deixados: string | null;
    cliente_confirmou: boolean;
}

interface ItemOrcamento {
    id: number;
    tipo: 'servico' | 'peca';
    servico: { id: number; nome: string } | null;
    descricao: string;
    quantidade: number;
    valor_unitario: string;
    status: 'pendente' | 'aprovado' | 'recusado';
    motivo_recusa: string | null;
    aprovado_por: { id: number; name: string } | null;
    aprovado_em: string | null;
}

interface ServicoResumo {
    id: number;
    nome: string;
    preco: string | null;
}

interface OrdemServico {
    id: number;
    status: string;
    reclamacao_cliente: string | null;
    diagnostico_tecnico: string | null;
    checklist_entrada: Checklist | null;
    created_at: string;
    cliente: { id: number; nome: string };
    veiculo: { id: number; placa: string; marca: MarcaModelo; modelo: MarcaModelo };
    unidade: { id: number; nome: string };
    itens: ItemOrcamento[];
    agendamento: { id: number; recurso: { id: number; tipo: string; user: { id: number; name: string } | null } | null } | null;
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

interface FormaPagamento {
    id: number;
    nome: string;
}

interface ProdutoResumo {
    id: number;
    nome: string;
    preco_venda: string;
    unidade_medida: string;
}

interface MembroEquipe {
    id: number;
    name: string;
}

const props = defineProps<{
    ordens: Paginado<OrdemServico>;
    agendamentosRecebidos: AgendamentoRecebido[];
    unidades: Unidade[];
    servicos: ServicoResumo[];
    formasPagamento: FormaPagamento[];
    produtos: ProdutoResumo[];
    equipe: MembroEquipe[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

function checklistVazio(): Checklist {
    return { km_entrada: null, nivel_combustivel: null, avarias: [], objetos_deixados: null, cliente_confirmou: false };
}

const modalAberto = ref(false);
const osEmEdicao = ref<OrdemServico | null>(null);
const novaAvaria = ref('');

const form = useForm({
    agendamento_id: null as number | null,
    unidade_id: null as number | null,
    cliente_id: null as number | null,
    veiculo_id: null as number | null,
    reclamacao_cliente: '',
    diagnostico_tecnico: '',
    checklist_entrada: checklistVazio(),
});

const veiculosDoClienteSelecionado = ref<Veiculo[]>([]);

function aoSelecionarCliente(cliente: Cliente | null) {
    veiculosDoClienteSelecionado.value = cliente?.veiculos ?? [];
    form.veiculo_id = null;
}

function abrirDeAgendamento(agendamento: AgendamentoRecebido) {
    osEmEdicao.value = null;
    form.reset();
    form.checklist_entrada = checklistVazio();
    form.agendamento_id = agendamento.id;
    form.unidade_id = agendamento.unidade_id;
    form.cliente_id = agendamento.cliente_id;
    form.veiculo_id = agendamento.veiculo_id;
    modalAberto.value = true;
}

function abrirAvulsa() {
    osEmEdicao.value = null;
    form.reset();
    form.checklist_entrada = checklistVazio();
    veiculosDoClienteSelecionado.value = [];
    modalAberto.value = true;
}

function abrirEdicao(os: OrdemServico) {
    osEmEdicao.value = os;
    form.reclamacao_cliente = os.reclamacao_cliente ?? '';
    form.diagnostico_tecnico = os.diagnostico_tecnico ?? '';
    form.checklist_entrada = os.checklist_entrada ? { ...checklistVazio(), ...os.checklist_entrada } : checklistVazio();

    // §04/§07 — se o agendamento tem um posto-pessoa vinculado a um técnico,
    // já sugere ele como responsável do próximo item (editável).
    const tecnicoDoPosto = os.agendamento?.recurso?.user ?? null;
    formItem.responsavel_id = tecnicoDoPosto?.id ?? null;

    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    osEmEdicao.value = null;
    novaAvaria.value = '';
    entregandoOs.value = false;
}

function adicionarAvaria() {
    const texto = novaAvaria.value.trim();
    if (!texto) return;
    form.checklist_entrada.avarias.push(texto);
    novaAvaria.value = '';
}

function removerAvaria(indice: number) {
    form.checklist_entrada.avarias.splice(indice, 1);
}

function salvar() {
    if (osEmEdicao.value) {
        form.put(route('ordens-servico.update', osEmEdicao.value.id), {
            onSuccess: () => fecharModal(),
        });
    } else {
        form.post(route('ordens-servico.store'), {
            onSuccess: () => fecharModal(),
        });
    }
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

const rotulosStatus: Record<string, string> = {
    aberta: 'Aberta',
    aguardando_aprovacao: 'Aguardando aprovação',
    em_execucao: 'Em execução',
    aguardando_peca: 'Aguardando peça',
    em_teste: 'Em teste',
    pronto: 'Pronto',
    entregue: 'Entregue',
};

const coresStatus: Record<string, string> = {
    aberta: 'bg-surface-200 text-sidebar-800',
    aguardando_aprovacao: 'bg-amber-50 text-amber-700',
    em_execucao: 'bg-blue-50 text-blue-700',
    aguardando_peca: 'bg-purple-50 text-purple-700',
    em_teste: 'bg-purple-50 text-purple-700',
    pronto: 'bg-green-50 text-green-700',
    entregue: 'bg-surface-200 text-sidebar-800/60',
};

// Depois de um reload parcial, `props.ordens.data` é substituído — precisa
// resincronizar a referência que o modal está segurando.
function resincronizarOsEmEdicao() {
    if (!osEmEdicao.value) return;
    const atualizada = props.ordens.data.find((o) => o.id === osEmEdicao.value?.id);
    if (atualizada) osEmEdicao.value = atualizada;
}

// Itens de orçamento
const formItem = useForm({
    tipo: 'servico' as 'servico' | 'peca',
    servico_id: null as number | null,
    produto_id: null as number | null,
    responsavel_id: null as number | null,
    descricao: '',
    quantidade: 1,
    valor_unitario: '',
});

function selecionarServicoDoItem(servicoId: number | null) {
    const servico = props.servicos.find((s) => s.id === servicoId);
    if (servico) {
        formItem.descricao = servico.nome;
        formItem.valor_unitario = servico.preco ?? '';
    }
}

function selecionarProdutoDoItem(produtoId: number | null) {
    const produto = props.produtos.find((p) => p.id === produtoId);
    if (produto) {
        formItem.descricao = produto.nome;
        formItem.valor_unitario = produto.preco_venda;
    } else {
        formItem.produto_id = null;
    }
}

function adicionarItem() {
    if (!osEmEdicao.value) return;

    formItem.post(route('ordens-servico.itens.store', osEmEdicao.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            formItem.reset();
            formItem.quantidade = 1;
            resincronizarOsEmEdicao();
        },
    });
}

function removerItem(item: ItemOrcamento) {
    router.delete(route('itens-orcamento.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => resincronizarOsEmEdicao(),
    });
}

function aprovarItem(item: ItemOrcamento) {
    router.post(
        route('itens-orcamento.aprovar', item.id),
        {},
        { preserveScroll: true, onSuccess: () => resincronizarOsEmEdicao() },
    );
}

function recusarItem(item: ItemOrcamento) {
    const motivo = prompt('Motivo da recusa (opcional):') ?? '';
    router.post(
        route('itens-orcamento.recusar', item.id),
        { motivo_recusa: motivo },
        { preserveScroll: true, onSuccess: () => resincronizarOsEmEdicao() },
    );
}

function valorTotalItem(item: ItemOrcamento): string {
    return (Number(item.valor_unitario) * item.quantidade).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

const ETAPAS_EXECUCAO = ['em_execucao', 'aguardando_peca', 'em_teste', 'pronto', 'entregue'];

const formEntrega = useForm({
    km_saida: '',
    forma_pagamento_id: null as number | null,
    numero_parcelas: 1,
});
const entregandoOs = ref(false);

function avancarStatusOs(os: OrdemServico, novoStatus: string) {
    if (novoStatus === 'entregue') {
        formEntrega.reset();
        entregandoOs.value = true;
        return;
    }

    router.patch(
        route('ordens-servico.status', os.id),
        { status: novoStatus },
        { preserveScroll: true, onSuccess: () => resincronizarOsEmEdicao() },
    );
}

function confirmarEntrega() {
    if (!osEmEdicao.value) return;

    formEntrega
        .transform((dados) => ({ ...dados, status: 'entregue' }))
        .patch(route('ordens-servico.status', osEmEdicao.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                entregandoOs.value = false;
                resincronizarOsEmEdicao();
            },
        });
}
</script>

<template>
    <Head title="Ordens de serviço" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Ordens de serviço</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">Do recebimento à entrega.</p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirAvulsa"
            >
                <PlusIcon class="h-4 w-4" />
                Abrir OS avulsa
            </button>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <!-- Atalho: agendamentos recebidos hoje ainda sem OS -->
        <div v-if="agendamentosRecebidos.length > 0" class="mt-6 rounded-xl border border-primary-200 bg-primary-50 p-4">
            <h2 class="mb-2 text-sm font-semibold text-primary-800">Veículos recebidos hoje sem OS</h2>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="agendamento in agendamentosRecebidos"
                    :key="agendamento.id"
                    type="button"
                    class="rounded-lg border border-primary-300 bg-white px-3 py-2 text-left text-sm hover:bg-primary-50"
                    @click="abrirDeAgendamento(agendamento)"
                >
                    <span class="font-medium text-sidebar-900">{{ agendamento.cliente.nome }}</span>
                    <span class="text-sidebar-800/60"> — {{ agendamento.veiculo.placa }} · {{ agendamento.hora_inicio }}</span>
                </button>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-xl border border-surface-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-xs uppercase text-sidebar-800/60">
                    <tr>
                        <th class="px-4 py-3">Cliente / Veículo</th>
                        <th class="px-4 py-3">Unidade</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aberta em</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="os in props.ordens.data" :key="os.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ os.cliente.nome }}</p>
                            <p class="text-xs text-sidebar-800/60">
                                {{ os.veiculo.marca.nome }} {{ os.veiculo.modelo.nome }} — {{ os.veiculo.placa }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">{{ os.unidade.nome }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[os.status]">
                                {{ rotulosStatus[os.status] ?? os.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800/70">{{ new Date(os.created_at).toLocaleString('pt-BR') }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="text-sm font-medium text-primary-600 hover:underline" @click="abrirEdicao(os)">
                                Ver / editar
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.ordens.data.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="5">
                            Nenhuma OS aberta ainda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.ordens.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.ordens.prev_page_url"
                @click="irPara(props.ordens.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">{{ props.ordens.current_page }} / {{ props.ordens.last_page }}</span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.ordens.next_page_url"
                @click="irPara(props.ordens.next_page_url)"
            >
                Próxima
            </button>
        </div>

        <!-- Modal: abrir / editar -->
        <Modal :open="modalAberto" :titulo="osEmEdicao ? 'OS #' + osEmEdicao.id : 'Nova ordem de serviço'" max-width="lg" @close="fecharModal">
            <form class="space-y-4" @submit.prevent="salvar">
                <template v-if="!osEmEdicao">
                    <div v-if="!form.agendamento_id" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                            <select
                                v-model="form.unidade_id"
                                required
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                            >
                                <option :value="null" disabled>Selecione</option>
                                <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                            </select>
                            <p v-if="form.errors.unidade_id" class="mt-1 text-xs text-red-600">{{ form.errors.unidade_id }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Cliente</label>
                            <ClienteBusca v-model="form.cliente_id" @select="aoSelecionarCliente" />
                            <p v-if="form.errors.cliente_id" class="mt-1 text-xs text-red-600">{{ form.errors.cliente_id }}</p>
                        </div>

                        <div class="sm:col-span-2">
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
                        </div>
                    </div>

                    <hr class="border-surface-200" />
                </template>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Reclamação do cliente</label>
                    <textarea
                        v-model="form.reclamacao_cliente"
                        rows="2"
                        placeholder="Nas palavras do cliente…"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                </div>

                <div v-if="osEmEdicao">
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Diagnóstico técnico</label>
                    <textarea
                        v-model="form.diagnostico_tecnico"
                        rows="2"
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400"
                    />
                </div>

                <div class="rounded-lg border border-surface-200 p-3">
                    <h3 class="mb-2 text-sm font-semibold text-sidebar-900">Checklist de entrada</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-sidebar-800">KM de entrada</label>
                            <input
                                v-model="form.checklist_entrada.km_entrada"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-surface-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-sidebar-800">Nível de combustível</label>
                            <select
                                v-model="form.checklist_entrada.nivel_combustivel"
                                class="w-full rounded-lg border border-surface-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400"
                            >
                                <option :value="null">—</option>
                                <option value="reserva">Reserva</option>
                                <option value="1/4">1/4</option>
                                <option value="1/2">1/2</option>
                                <option value="3/4">3/4</option>
                                <option value="cheio">Cheio</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-sidebar-800">Avarias</label>
                        <div class="mb-2 flex flex-wrap gap-1">
                            <span
                                v-for="(avaria, indice) in form.checklist_entrada.avarias"
                                :key="indice"
                                class="flex items-center gap-1 rounded-full bg-surface-100 px-2 py-1 text-xs text-sidebar-800"
                            >
                                {{ avaria }}
                                <button type="button" class="text-red-600" @click="removerAvaria(indice)">×</button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input
                                v-model="novaAvaria"
                                type="text"
                                placeholder="Ex: risco na porta traseira"
                                class="flex-1 rounded-lg border border-surface-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400"
                                @keydown.enter.prevent="adicionarAvaria"
                            />
                            <button
                                type="button"
                                class="rounded-lg border border-surface-200 px-3 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                                @click="adicionarAvaria"
                            >
                                Adicionar
                            </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-sidebar-800">Objetos deixados no veículo</label>
                        <input
                            v-model="form.checklist_entrada.objetos_deixados"
                            type="text"
                            class="w-full rounded-lg border border-surface-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                    </div>

                    <label class="mt-3 flex items-center gap-2 text-xs text-sidebar-800">
                        <input v-model="form.checklist_entrada.cliente_confirmou" type="checkbox" class="rounded border-surface-200" />
                        Cliente confirmou o checklist
                    </label>
                </div>

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
                        {{ osEmEdicao ? 'Salvar' : 'Abrir OS' }}
                    </button>
                </div>
            </form>

            <!-- Orçamento: só depois que a OS já existe -->
            <div v-if="osEmEdicao" class="mt-6 border-t border-surface-200 pt-5">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-sidebar-900">Orçamento</h3>
                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="coresStatus[osEmEdicao.status]">
                        {{ rotulosStatus[osEmEdicao.status] ?? osEmEdicao.status }}
                    </span>
                </div>

                <ul class="mb-3 space-y-2">
                    <li
                        v-for="item in osEmEdicao.itens"
                        :key="item.id"
                        class="rounded-lg border border-surface-200 p-2.5 text-sm"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-sidebar-900">
                                    {{ item.descricao }}
                                    <span class="text-xs font-normal text-sidebar-800/50">
                                        ({{ item.tipo === 'servico' ? 'serviço' : 'peça' }})
                                    </span>
                                </p>
                                <p class="text-xs text-sidebar-800/60">
                                    {{ item.quantidade }}× {{ Number(item.valor_unitario).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                                    = <strong>{{ valorTotalItem(item) }}</strong>
                                </p>
                                <p v-if="item.status === 'recusado' && item.motivo_recusa" class="text-xs text-red-600">
                                    Recusado: {{ item.motivo_recusa }}
                                </p>
                                <p v-if="item.aprovado_por" class="text-[11px] text-sidebar-800/50">
                                    {{ item.status === 'aprovado' ? 'Aprovado' : 'Recusado' }} por {{ item.aprovado_por.name }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="{
                                        'bg-surface-200 text-sidebar-800': item.status === 'pendente',
                                        'bg-green-50 text-green-700': item.status === 'aprovado',
                                        'bg-red-50 text-red-700': item.status === 'recusado',
                                    }"
                                >
                                    {{ item.status === 'pendente' ? 'Pendente' : item.status === 'aprovado' ? 'Aprovado' : 'Recusado' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="item.status === 'pendente'" class="mt-2 flex gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-primary-400 px-2.5 py-1 text-xs font-semibold text-sidebar-950 hover:opacity-90"
                                @click="aprovarItem(item)"
                            >
                                Cliente aprovou
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-surface-200 px-2.5 py-1 text-xs font-medium text-sidebar-800 hover:bg-surface-50"
                                @click="recusarItem(item)"
                            >
                                Cliente recusou
                            </button>
                            <button
                                type="button"
                                class="ml-auto text-xs text-red-600 hover:underline"
                                @click="removerItem(item)"
                            >
                                Remover
                            </button>
                        </div>
                    </li>

                    <li v-if="osEmEdicao.itens.length === 0" class="text-xs text-sidebar-800/60">
                        Nenhum item no orçamento ainda.
                    </li>
                </ul>

                <form class="space-y-2 rounded-lg border border-surface-200 p-3" @submit.prevent="adicionarItem">
                    <div class="grid grid-cols-2 gap-2">
                        <select
                            v-model="formItem.tipo"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        >
                            <option value="servico">Serviço</option>
                            <option value="peca">Peça</option>
                        </select>

                        <select
                            v-if="formItem.tipo === 'servico'"
                            v-model="formItem.servico_id"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            @change="selecionarServicoDoItem(formItem.servico_id)"
                        >
                            <option :value="null" disabled>Selecione</option>
                            <option v-for="s in servicos" :key="s.id" :value="s.id">{{ s.nome }}</option>
                        </select>

                        <select
                            v-if="formItem.tipo === 'peca'"
                            v-model="formItem.produto_id"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            @change="selecionarProdutoDoItem(formItem.produto_id)"
                        >
                            <option :value="null">Texto livre (sem baixa de estoque)</option>
                            <option v-for="p in produtos" :key="p.id" :value="p.id">{{ p.nome }}</option>
                        </select>
                    </div>

                    <p v-if="formItem.tipo === 'peca' && formItem.produto_id" class="text-[11px] text-sidebar-800/50">
                        Vinculado ao estoque — aprovar esse item dá baixa automática.
                    </p>

                    <select
                        v-model="formItem.responsavel_id"
                        class="w-full rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                    >
                        <option :value="null">Responsável pelo item (opcional — sem isso não gera comissão)</option>
                        <option v-for="membro in equipe" :key="membro.id" :value="membro.id">{{ membro.name }}</option>
                    </select>

                    <input
                        v-model="formItem.descricao"
                        type="text"
                        required
                        placeholder="Descrição"
                        class="w-full rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                    />

                    <div class="grid grid-cols-2 gap-2">
                        <input
                            v-model="formItem.quantidade"
                            type="number"
                            min="1"
                            placeholder="Qtd."
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                        <input
                            v-model="formItem.valor_unitario"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Valor unitário"
                            class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="formItem.processing"
                        class="w-full rounded-lg border border-surface-200 py-1.5 text-sm font-medium text-sidebar-800 hover:bg-surface-50"
                    >
                        Adicionar ao orçamento
                    </button>
                </form>

                <!-- Avançar etapa de execução (só depois que tem item aprovado) -->
                <div
                    v-if="ETAPAS_EXECUCAO.includes(osEmEdicao.status) && osEmEdicao.status !== 'entregue'"
                    class="mt-4"
                >
                    <label class="mb-1 block text-xs font-medium text-sidebar-800">Avançar etapa</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="etapa in ETAPAS_EXECUCAO"
                            :key="etapa"
                            type="button"
                            class="rounded-lg border border-surface-200 px-3 py-1.5 text-xs font-medium hover:bg-surface-50"
                            :class="osEmEdicao.status === etapa ? 'border-primary-400 bg-primary-50 text-primary-700' : 'text-sidebar-800'"
                            @click="avancarStatusOs(osEmEdicao, etapa)"
                        >
                            {{ rotulosStatus[etapa] }}
                        </button>
                    </div>

                    <form
                        v-if="entregandoOs"
                        class="mt-3 space-y-2 rounded-lg border border-surface-200 p-3"
                        @submit.prevent="confirmarEntrega"
                    >
                        <p class="text-xs font-medium text-sidebar-900">Confirmar entrega</p>

                        <select
                            v-model="formEntrega.forma_pagamento_id"
                            class="w-full rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                        >
                            <option :value="null">Forma de pagamento (opcional)</option>
                            <option v-for="f in formasPagamento" :key="f.id" :value="f.id">{{ f.nome }}</option>
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <input
                                v-model="formEntrega.numero_parcelas"
                                type="number"
                                min="1"
                                max="12"
                                placeholder="Nº de parcelas"
                                class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                            <input
                                v-model="formEntrega.km_saida"
                                type="number"
                                min="0"
                                placeholder="KM de saída"
                                class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                            />
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-surface-200 py-1.5 text-xs font-medium text-sidebar-800 hover:bg-surface-50"
                                @click="entregandoOs = false"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formEntrega.processing"
                                class="flex-1 rounded-lg bg-primary-400 py-1.5 text-xs font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                            >
                                Confirmar entrega
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>
    </PainelLayout>
</template>
