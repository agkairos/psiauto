<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { MASCARA_CNPJ } from '@/lib/masks';

const segmentosDisponiveis = [
    { value: 'mecanica', label: 'Mecânica geral' },
    { value: 'eletrica', label: 'Elétrica automotiva' },
    { value: 'funilaria', label: 'Funilaria' },
    { value: 'estetica', label: 'Estética automotiva' },
    { value: 'pecas', label: 'Casa de peças' },
];

const form = useForm({
    razao_social: '',
    nome_fantasia: '',
    cnpj: '',
    segmentos: [] as string[],
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('registrar'));
}
</script>

<template>
    <Head title="Cadastrar empresa" />

    <GuestLayout>
        <h1 class="text-lg font-semibold text-sidebar-900">Cadastre sua empresa</h1>
        <p class="mt-1 text-sm text-sidebar-800/60">
            Você vira o proprietário da conta — convide sua equipe depois de dentro do painel.
        </p>

        <a
            :href="route('auth.google.redirect')"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg border border-surface-200 bg-white py-2.5 text-sm font-medium text-sidebar-900 transition-colors hover:bg-surface-50"
        >
            Cadastrar com Google
        </a>

        <div class="my-5 flex items-center gap-3 text-xs text-sidebar-800/50">
            <div class="h-px flex-1 bg-surface-200" />
            ou
            <div class="h-px flex-1 bg-surface-200" />
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Razão social</label>
                    <input
                        v-model="form.razao_social"
                        type="text"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                    <p v-if="form.errors.razao_social" class="mt-1 text-xs text-red-600">
                        {{ form.errors.razao_social }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Nome fantasia</label>
                    <input
                        v-model="form.nome_fantasia"
                        type="text"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                    <p v-if="form.errors.nome_fantasia" class="mt-1 text-xs text-red-600">
                        {{ form.errors.nome_fantasia }}
                    </p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-sidebar-900">CNPJ</label>
                <input
                    v-model="form.cnpj"
                    v-maska
                    :data-maska="MASCARA_CNPJ"
                    type="text"
                    required
                    placeholder="00.000.000/0000-00"
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                />
                <p v-if="form.errors.cnpj" class="mt-1 text-xs text-red-600">{{ form.errors.cnpj }}</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-sidebar-900">Segmentos atendidos</label>
                <div class="grid grid-cols-2 gap-2">
                    <label
                        v-for="segmento in segmentosDisponiveis"
                        :key="segmento.value"
                        class="flex items-center gap-2 rounded-lg border border-surface-200 px-3 py-2 text-sm text-sidebar-800"
                    >
                        <input
                            v-model="form.segmentos"
                            type="checkbox"
                            :value="segmento.value"
                            class="rounded border-surface-200"
                        />
                        {{ segmento.label }}
                    </label>
                </div>
                <p v-if="form.errors.segmentos" class="mt-1 text-xs text-red-600">
                    {{ form.errors.segmentos }}
                </p>
            </div>

            <hr class="border-surface-200" />

            <div>
                <label class="mb-1 block text-sm font-medium text-sidebar-900">Seu nome</label>
                <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-sidebar-900">Seu e-mail</label>
                <input
                    v-model="form.email"
                    type="email"
                    required
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Senha</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-sidebar-900">Confirmar senha</label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                Criar conta da empresa
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-sidebar-800/70">
            Já tem conta?
            <Link :href="route('login')" class="font-medium text-primary-600 hover:underline">Entrar</Link>
        </p>
    </GuestLayout>
</template>
