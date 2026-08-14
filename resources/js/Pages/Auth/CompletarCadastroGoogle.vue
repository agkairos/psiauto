<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { MASCARA_CNPJ } from '@/lib/masks';

defineProps<{
    nome: string;
    email: string;
}>();

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
});

function submit() {
    form.post(route('registrar.completar'));
}
</script>

<template>
    <Head title="Completar cadastro" />

    <GuestLayout>
        <h1 class="text-lg font-semibold text-sidebar-900">Falta pouco, {{ nome.split(' ')[0] }}</h1>
        <p class="mt-1 text-sm text-sidebar-800/60">
            Conectado como <strong>{{ email }}</strong> — agora só os dados da sua empresa.
        </p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 transition-opacity hover:opacity-90 disabled:opacity-50"
            >
                Concluir cadastro
            </button>
        </form>
    </GuestLayout>
</template>
