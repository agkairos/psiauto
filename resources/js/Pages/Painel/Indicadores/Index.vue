<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';

interface Unidade {
    id: number;
    nome: string;
}

interface FaturamentoServico {
    descricao: string;
    total: string;
}

interface Conversao {
    enviados: number;
    aprovados: number;
    percentual: number;
}

interface Ocupacao {
    minutos_ocupados: number;
    minutos_disponiveis: number;
    percentual: number;
}

interface RetornoJanela {
    total_clientes: number;
    recorrentes: number;
    percentual: number;
}

const props = defineProps<{
    filtros: { data_inicio: string; data_fim: string; unidade_id: number | null };
    unidades: Unidade[];
    faturamentoTotal: string;
    ticketMedio: string;
    faturamentoPorServico: FaturamentoServico[];
    conversaoOrcamento: Conversao;
    ocupacaoAgenda: Ocupacao;
    permanenciaMediaHoras: number | null;
    retornoClientes: { seis_meses: RetornoJanela; doze_meses: RetornoJanela };
}>();

const dataInicio = ref(props.filtros.data_inicio);
const dataFim = ref(props.filtros.data_fim);
const unidadeId = ref(props.filtros.unidade_id ?? '');

function aplicarFiltros() {
    router.get(
        route('indicadores.index'),
        { data_inicio: dataInicio.value, data_fim: dataFim.value, unidade_id: unidadeId.value || undefined },
        { preserveState: true },
    );
}

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function horasParaTexto(minutos: number): string {
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    return `${h}h${m > 0 ? m + 'min' : ''}`;
}
</script>

<template>
    <Head title="Indicadores" />

    <PainelLayout>
        <div>
            <h1 class="text-xl font-semibold text-sidebar-900">Indicadores</h1>
            <p class="mt-1 text-sm text-sidebar-800/60">Painel gerencial — resultado do que já passou pelo sistema.</p>
        </div>

        <div class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-surface-200 bg-white p-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-sidebar-800">De</label>
                <input
                    v-model="dataInicio"
                    type="date"
                    class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-sidebar-800">Até</label>
                <input
                    v-model="dataFim"
                    type="date"
                    class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-sidebar-800">Unidade</label>
                <select
                    v-model="unidadeId"
                    class="rounded-lg border border-surface-200 px-2 py-1.5 text-sm outline-none focus:border-primary-400"
                >
                    <option value="">Todas</option>
                    <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option>
                </select>
            </div>
            <button
                type="button"
                class="rounded-lg bg-primary-400 px-4 py-1.5 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="aplicarFiltros"
            >
                Aplicar
            </button>
        </div>

        <!-- Cards principais -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Faturamento no período</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ moeda(faturamentoTotal) }}</p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Ticket médio</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ moeda(ticketMedio) }}</p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Conversão de orçamento</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ conversaoOrcamento.percentual }}%</p>
                <p class="mt-1 text-xs text-sidebar-800/50">
                    {{ conversaoOrcamento.aprovados }} de {{ conversaoOrcamento.enviados }} OS com item aprovado
                </p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Ocupação da agenda</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ ocupacaoAgenda.percentual }}%</p>
                <p class="mt-1 text-xs text-sidebar-800/50">
                    {{ horasParaTexto(ocupacaoAgenda.minutos_ocupados) }} de
                    {{ horasParaTexto(ocupacaoAgenda.minutos_disponiveis) }} disponíveis
                </p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Permanência média do veículo</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">
                    {{ permanenciaMediaHoras !== null ? permanenciaMediaHoras + 'h' : '—' }}
                </p>
                <p class="mt-1 text-xs text-sidebar-800/50">Da abertura da OS até a entrega</p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Retorno de clientes (6 meses)</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ retornoClientes.seis_meses.percentual }}%</p>
                <p class="mt-1 text-xs text-sidebar-800/50">
                    {{ retornoClientes.seis_meses.recorrentes }} de {{ retornoClientes.seis_meses.total_clientes }} voltaram
                </p>
            </div>

            <div class="rounded-xl border border-surface-200 bg-white p-4">
                <p class="text-xs font-medium text-sidebar-800/60">Retorno de clientes (12 meses)</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">{{ retornoClientes.doze_meses.percentual }}%</p>
                <p class="mt-1 text-xs text-sidebar-800/50">
                    {{ retornoClientes.doze_meses.recorrentes }} de {{ retornoClientes.doze_meses.total_clientes }} voltaram
                </p>
            </div>

            <!-- Indisponíveis — dependem de módulos ainda não implementados -->
            <div class="rounded-xl border border-dashed border-surface-300 bg-surface-50 p-4 opacity-60">
                <p class="text-xs font-medium text-sidebar-800/60">Produção por técnico / retrabalho</p>
                <p class="mt-2 text-sm text-sidebar-800/50">
                    Indisponível — precisa de um vínculo técnico responsável na OS, que ainda não existe.
                </p>
            </div>

            <div class="rounded-xl border border-dashed border-surface-300 bg-surface-50 p-4 opacity-60">
                <p class="text-xs font-medium text-sidebar-800/60">Giro de estoque / produtos parados</p>
                <p class="mt-2 text-sm text-sidebar-800/50">
                    Indisponível — depende do módulo de Produtos e estoque (§11), ainda não implementado.
                </p>
            </div>
        </div>

        <!-- Faturamento por serviço -->
        <div class="mt-6 rounded-xl border border-surface-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-sidebar-900">Faturamento por serviço</h2>
            <div v-if="faturamentoPorServico.length === 0" class="text-sm text-sidebar-800/60">
                Nenhum item aprovado faturado nesse período.
            </div>
            <ul v-else class="space-y-2">
                <li
                    v-for="linha in faturamentoPorServico"
                    :key="linha.descricao"
                    class="flex items-center justify-between border-b border-surface-100 pb-2 text-sm last:border-0"
                >
                    <span class="text-sidebar-800">{{ linha.descricao }}</span>
                    <span class="font-medium text-sidebar-900">{{ moeda(linha.total) }}</span>
                </li>
            </ul>
        </div>
    </PainelLayout>
</template>
