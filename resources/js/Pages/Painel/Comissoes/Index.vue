<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';

interface Comissao {
    id: number;
    valor_base: string;
    percentual: string;
    valor_comissao: string;
    status: 'pendente' | 'paga';
    pago_em: string | null;
    created_at: string;
    responsavel: { id: number; name: string };
    item: { id: number; descricao: string };
    ordem_servico: { id: number };
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    comissoes: Paginado<Comissao>;
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

function moeda(valor: string | number): string {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function marcarPaga(comissao: Comissao) {
    if (!confirm(`Marcar a comissão de ${comissao.responsavel.name} (${moeda(comissao.valor_comissao)}) como paga?`)) return;
    router.post(route('comissoes.pagar', comissao.id), {}, { preserveScroll: true });
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true, preserveState: true });
}

const totalPendente = computed(() =>
    props.comissoes.data.filter((c) => c.status === 'pendente').reduce((soma, c) => soma + Number(c.valor_comissao), 0),
);
</script>

<template>
    <Head title="Comissões" />

    <PainelLayout>
        <div>
            <h1 class="text-xl font-semibold text-sidebar-900">Comissões</h1>
            <p class="mt-1 text-sm text-sidebar-800/60">Geradas automaticamente quando um item com responsável é aprovado.</p>
        </div>

        <div
            v-if="mensagemSucesso"
            class="mt-4 rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm text-primary-800"
        >
            {{ mensagemSucesso }}
        </div>

        <div class="mt-4 rounded-xl border border-surface-200 bg-white p-4">
            <p class="text-xs font-medium text-sidebar-800/60">Pendente nesta página</p>
            <p class="mt-1 text-xl font-semibold text-sidebar-900">{{ moeda(totalPendente) }}</p>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-surface-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-xs uppercase text-sidebar-800/60">
                    <tr>
                        <th class="px-4 py-3">Responsável</th>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Base</th>
                        <th class="px-4 py-3">%</th>
                        <th class="px-4 py-3">Comissão</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="comissao in props.comissoes.data" :key="comissao.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-sidebar-900">{{ comissao.responsavel.name }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ comissao.item.descricao }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ moeda(comissao.valor_base) }}</td>
                        <td class="px-4 py-3 text-sidebar-800">{{ comissao.percentual }}%</td>
                        <td class="px-4 py-3 font-medium text-sidebar-900">{{ moeda(comissao.valor_comissao) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="comissao.status === 'paga' ? 'bg-green-50 text-green-700' : 'bg-surface-200 text-sidebar-800'"
                            >
                                {{ comissao.status === 'paga' ? 'Paga' : 'Pendente' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="comissao.status === 'pendente'"
                                type="button"
                                class="text-sm font-medium text-primary-600 hover:underline"
                                @click="marcarPaga(comissao)"
                            >
                                Marcar paga
                            </button>
                        </td>
                    </tr>

                    <tr v-if="props.comissoes.data.length === 0">
                        <td class="px-4 py-6 text-center text-sm text-sidebar-800/60" colspan="7">
                            Nenhuma comissão gerada ainda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.comissoes.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.comissoes.prev_page_url"
                @click="irPara(props.comissoes.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">{{ props.comissoes.current_page }} / {{ props.comissoes.last_page }}</span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.comissoes.next_page_url"
                @click="irPara(props.comissoes.next_page_url)"
            >
                Próxima
            </button>
        </div>
    </PainelLayout>
</template>
