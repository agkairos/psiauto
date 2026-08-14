<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps<{
    nome: string;
    email: string;
    url: string;
}>();

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(props.url);
}
</script>

<template>
    <Head title="Aceitar convite" />

    <GuestLayout>
        <h1 class="text-lg font-semibold text-sidebar-900">Olá, {{ nome.split(' ')[0] }}</h1>
        <p class="mt-1 text-sm text-sidebar-800/60">
            Você foi convidado para o painel da PsiAuto como <strong>{{ email }}</strong>. Defina
            sua senha para começar.
        </p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium text-sidebar-900">Senha</label>
                <input
                    v-model="form.password"
                    type="password"
                    required
                    autofocus
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                Definir senha e entrar
            </button>
        </form>
    </GuestLayout>
</template>
