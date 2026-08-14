<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { MASCARA_CEP } from '@/lib/masks';

interface Unidade {
    id: number;
    nome: string;
    cep: string | null;
    logradouro: string | null;
    numero: string | null;
    complemento: string | null;
    bairro: string | null;
    cidade: string | null;
    uf: string | null;
    ativa: boolean;
}

const props = defineProps<{
    unidades: Unidade[];
}>();

const page = usePage();
const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);

const modalAberto = ref(false);
const unidadeEmEdicao = ref<Unidade | null>(null);

const form = useForm({
    nome: '',
    cep: '',
    logradouro: '',
    numero: '',
    complemento: '',
    bairro: '',
    cidade: '',
    uf: '',
    ativa: true,
});

function abrirCriacao() {
    unidadeEmEdicao.value = null;
    form.reset();
    form.ativa = true;
    modalAberto.value = true;
}

function abrirEdicao(unidade: Unidade) {
    unidadeEmEdicao.value = unidade;
    form.nome = unidade.nome;
    form.cep = unidade.cep ?? '';
    form.logradouro = unidade.logradouro ?? '';
    form.numero = unidade.numero ?? '';
    form.complemento = unidade.complemento ?? '';
    form.bairro = unidade.bairro ?? '';
    form.cidade = unidade.cidade ?? '';
    form.uf = unidade.uf ?? '';
    form.ativa = unidade.ativa;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    unidadeEmEdicao.value = null;
}

function salvar() {
    if (unidadeEmEdicao.value) {
        form.put(route('unidades.update', unidadeEmEdicao.value.id), {
            onSuccess: () => fecharModal(),
        });
    } else {
        form.post(route('unidades.store'), {
            onSuccess: () => fecharModal(),
        });
    }
}

function remover(unidade: Unidade) {
    if (!confirm(`Remover a unidade "${unidade.nome}"? Isso não pode ser desfeito.`)) {
        return;
    }

    router.delete(route('unidades.destroy', unidade.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Unidades" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Unidades</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">
                    As lojas da sua empresa — cada uma com endereço, agenda e estoque próprios.
                </p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirCriacao"
            >
                <PlusIcon class="h-4 w-4" />
                Nova unidade
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
                v-for="unidade in props.unidades"
                :key="unidade.id"
                class="rounded-xl border border-surface-200 bg-white p-4"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-sidebar-900">{{ unidade.nome }}</p>
                        <p class="mt-1 text-xs text-sidebar-800/60">
                            <template v-if="unidade.logradouro">
                                {{ unidade.logradouro }}, {{ unidade.numero || 's/n' }} —
                                {{ unidade.cidade }}/{{ unidade.uf }}
                            </template>
                            <template v-else>Endereço não informado</template>
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2 py-1 text-xs font-medium"
                        :class="unidade.ativa ? 'bg-primary-50 text-primary-700' : 'bg-surface-200 text-sidebar-800/60'"
                    >
                        {{ unidade.ativa ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>

                <div class="mt-4 flex gap-3 text-sm">
                    <button type="button" class="font-medium text-primary-600 hover:underline" @click="abrirEdicao(unidade)">
                        Editar
                    </button>
                    <button type="button" class="font-medium text-red-600 hover:underline" @click="remover(unidade)">
                        Remover
                    </button>
                </div>
            </div>

            <p v-if="props.unidades.length === 0" class="text-sm text-sidebar-800/60">
                Nenhuma unidade cadastrada ainda.
            </p>
        </div>

        <!-- Modal: criar / editar -->
        <Modal :open="modalAberto" :titulo="unidadeEmEdicao ? 'Editar unidade' : 'Nova unidade'" @close="fecharModal">
            <form class="space-y-4" @submit.prevent="salvar">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome</label>
                            <input
                                v-model="form.nome"
                                type="text"
                                required
                                placeholder="Matriz, Loja Centro…"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            />
                            <p v-if="form.errors.nome" class="mt-1 text-xs text-red-600">{{ form.errors.nome }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">CEP</label>
                                <input
                                    v-model="form.cep"
                                    v-maska
                                    :data-maska="MASCARA_CEP"
                                    type="text"
                                    placeholder="00000-000"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                                <p v-if="form.errors.cep" class="mt-1 text-xs text-red-600">{{ form.errors.cep }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">UF</label>
                                <input
                                    v-model="form.uf"
                                    type="text"
                                    maxlength="2"
                                    placeholder="SP"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm uppercase outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Logradouro</label>
                            <input
                                v-model="form.logradouro"
                                type="text"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Número</label>
                                <input
                                    v-model="form.numero"
                                    type="text"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Complemento</label>
                                <input
                                    v-model="form.complemento"
                                    type="text"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Bairro</label>
                                <input
                                    v-model="form.bairro"
                                    type="text"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-sidebar-900">Cidade</label>
                                <input
                                    v-model="form.cidade"
                                    type="text"
                                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                                />
                            </div>
                        </div>

                        <label v-if="unidadeEmEdicao" class="flex items-center gap-2 text-sm text-sidebar-800/80">
                            <input v-model="form.ativa" type="checkbox" class="rounded border-surface-200" />
                            Unidade ativa
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
