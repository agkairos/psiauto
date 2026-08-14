<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import CarDoorIcon from '@/Components/Icons/CarDoorIcon.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Entrar" />

    <GuestLayout>
        <h1 class="text-lg font-semibold text-sidebar-900">Entrar no painel</h1>
        <p class="mt-1 text-sm text-sidebar-800/60">Acesso da equipe da sua empresa.</p>

        <a
            :href="route('auth.google.redirect')"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg border border-surface-200 bg-white py-2.5 text-sm font-medium text-sidebar-900 transition-colors hover:bg-surface-50"
        >
            Entrar com Google
        </a>

        <div class="my-5 flex items-center gap-3 text-xs text-sidebar-800/50">
            <div class="h-px flex-1 bg-surface-200" />
            ou
            <div class="h-px flex-1 bg-surface-200" />
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-sidebar-900">
                    E-mail
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-sidebar-900">
                    Senha
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm text-sidebar-800/80">
                <input v-model="form.remember" type="checkbox" class="rounded border-surface-200" />
                Lembrar de mim
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                <CarDoorIcon class="h-5 w-5" />
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-sidebar-800/70">
            Sua empresa ainda não tem conta?
            <Link :href="route('registrar')" class="font-medium text-primary-600 hover:underline">
                Cadastre sua empresa
            </Link>
        </p>
    </GuestLayout>
</template>
