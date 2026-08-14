<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PainelLayout from '@/Layouts/PainelLayout.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

interface Unidade {
    id: number;
    nome: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
    ativo: boolean;
    unidade: Unidade | null;
    roles: { name: string }[];
}

interface Paginado<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    usuarios: Paginado<Usuario>;
    unidades: Unidade[];
}>();

const rotulosPerfil: Record<string, string> = {
    proprietario: 'Proprietário',
    gerente: 'Gerente',
    atendente: 'Atendente',
    tecnico: 'Técnico',
    financeiro: 'Financeiro',
};

const perfisConvidaveis = ['gerente', 'atendente', 'tecnico', 'financeiro'];

const page = usePage();

const modalAberto = ref(false);
const usuarioEmEdicao = ref<Usuario | null>(null);

const formConvite = useForm({
    name: '',
    email: '',
    role: 'atendente',
    unidade_id: null as number | null,
});

const formEdicao = useForm({
    role: 'atendente',
    unidade_id: null as number | null,
});

function abrirConvite() {
    usuarioEmEdicao.value = null;
    formConvite.reset();
    modalAberto.value = true;
}

function abrirEdicao(usuario: Usuario) {
    usuarioEmEdicao.value = usuario;
    formEdicao.role = usuario.roles[0]?.name ?? 'atendente';
    formEdicao.unidade_id = usuario.unidade?.id ?? null;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    usuarioEmEdicao.value = null;
}

function salvarConvite() {
    formConvite.post(route('usuarios.store'), {
        onSuccess: () => fecharModal(),
    });
}

function salvarEdicao() {
    if (!usuarioEmEdicao.value) return;

    formEdicao.put(route('usuarios.update', usuarioEmEdicao.value.id), {
        onSuccess: () => fecharModal(),
    });
}

function desativar(usuario: Usuario) {
    if (!confirm(`Desativar ${usuario.name}? Ele perde o acesso ao painel imediatamente.`)) {
        return;
    }

    router.delete(route('usuarios.destroy', usuario.id), { preserveScroll: true });
}

function reativar(usuario: Usuario) {
    router.post(route('usuarios.reativar', usuario.id), {}, { preserveScroll: true });
}

function irPara(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true });
}

const mensagemSucesso = computed(() => (page.props.flash as { sucesso?: string } | undefined)?.sucesso);
</script>

<template>
    <Head title="Usuários" />

    <PainelLayout>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-sidebar-900">Usuários</h1>
                <p class="mt-1 text-sm text-sidebar-800/60">
                    Quem acessa o painel da empresa e o que cada um pode fazer.
                </p>
            </div>

            <button
                type="button"
                class="flex items-center gap-2 rounded-lg bg-primary-400 px-4 py-2 text-sm font-semibold text-sidebar-950 hover:opacity-90"
                @click="abrirConvite"
            >
                <PlusIcon class="h-4 w-4" />
                Convidar usuário
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
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Perfil</th>
                        <th class="px-4 py-3">Unidade</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="usuario in props.usuarios.data" :key="usuario.id" class="border-b border-surface-100 last:border-0">
                        <td class="px-4 py-3">
                            <p class="font-medium text-sidebar-900">{{ usuario.name }}</p>
                            <p class="text-xs text-sidebar-800/60">{{ usuario.email }}</p>
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">
                            {{ rotulosPerfil[usuario.roles[0]?.name ?? ''] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sidebar-800">
                            {{ usuario.unidade?.nome ?? 'Todas' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="usuario.ativo ? 'bg-primary-50 text-primary-700' : 'bg-surface-200 text-sidebar-800/60'"
                            >
                                {{ usuario.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="usuario.roles[0]?.name !== 'proprietario'"
                                type="button"
                                class="mr-3 text-sm font-medium text-primary-600 hover:underline"
                                @click="abrirEdicao(usuario)"
                            >
                                Editar
                            </button>
                            <button
                                v-if="usuario.roles[0]?.name !== 'proprietario' && usuario.ativo"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:underline"
                                @click="desativar(usuario)"
                            >
                                Desativar
                            </button>
                            <button
                                v-else-if="usuario.roles[0]?.name !== 'proprietario'"
                                type="button"
                                class="text-sm font-medium text-sidebar-800 hover:underline"
                                @click="reativar(usuario)"
                            >
                                Reativar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="props.usuarios.last_page > 1" class="mt-4 flex justify-center gap-3 text-sm">
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.usuarios.prev_page_url"
                @click="irPara(props.usuarios.prev_page_url)"
            >
                Anterior
            </button>
            <span class="text-sidebar-800/60">
                {{ props.usuarios.current_page }} / {{ props.usuarios.last_page }}
            </span>
            <button
                type="button"
                class="text-sidebar-800 disabled:opacity-40"
                :disabled="!props.usuarios.next_page_url"
                @click="irPara(props.usuarios.next_page_url)"
            >
                Próxima
            </button>
        </div>

        <!-- Modal: convidar / editar -->
        <Modal
            :open="modalAberto"
            :titulo="usuarioEmEdicao ? 'Editar usuário' : 'Convidar usuário'"
            @close="fecharModal"
        >
            <form v-if="!usuarioEmEdicao" class="space-y-4" @submit.prevent="salvarConvite">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome</label>
                            <input
                                v-model="formConvite.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            />
                            <p v-if="formConvite.errors.name" class="mt-1 text-xs text-red-600">
                                {{ formConvite.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">E-mail</label>
                            <input
                                v-model="formConvite.email"
                                type="email"
                                required
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            />
                            <p v-if="formConvite.errors.email" class="mt-1 text-xs text-red-600">
                                {{ formConvite.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Perfil</label>
                            <select
                                v-model="formConvite.role"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            >
                                <option v-for="perfil in perfisConvidaveis" :key="perfil" :value="perfil">
                                    {{ rotulosPerfil[perfil] }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                            <select
                                v-model="formConvite.unidade_id"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            >
                                <option :value="null">Todas as unidades</option>
                                <option v-for="unidade in unidades" :key="unidade.id" :value="unidade.id">
                                    {{ unidade.nome }}
                                </option>
                            </select>
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
                                :disabled="formConvite.processing"
                                class="flex-1 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                            >
                                Enviar convite
                            </button>
                        </div>
                    </form>

                    <form v-else class="space-y-4" @submit.prevent="salvarEdicao">
                        <p class="text-sm text-sidebar-800/70">
                            {{ usuarioEmEdicao.name }} — {{ usuarioEmEdicao.email }}
                        </p>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Perfil</label>
                            <select
                                v-model="formEdicao.role"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            >
                                <option v-for="perfil in perfisConvidaveis" :key="perfil" :value="perfil">
                                    {{ rotulosPerfil[perfil] }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-sidebar-900">Unidade</label>
                            <select
                                v-model="formEdicao.unidade_id"
                                class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                            >
                                <option :value="null">Todas as unidades</option>
                                <option v-for="unidade in unidades" :key="unidade.id" :value="unidade.id">
                                    {{ unidade.nome }}
                                </option>
                            </select>
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
                                :disabled="formEdicao.processing"
                                class="flex-1 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 hover:opacity-90 disabled:opacity-50"
                            >
                                Salvar
                            </button>
                        </div>
                    </form>
        </Modal>
    </PainelLayout>
</template>
