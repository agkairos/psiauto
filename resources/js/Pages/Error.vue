<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ClockIcon,
    ExclamationTriangleIcon,
    LockClosedIcon,
    MapIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    status: number;
}>();

interface SharedProps {
    auth?: { user: { id: number } | null };
    [key: string]: unknown;
}

const page = usePage<SharedProps>();
const autenticado = computed(() => (page.props.auth?.user ?? null) !== null);

const conteudo = computed(() => {
    const mapa: Record<number, { titulo: string; descricao: string; icon: typeof LockClosedIcon }> = {
        403: {
            titulo: 'Sem permissão',
            descricao: 'Sua conta não tem acesso a essa parte do painel. Se acha que deveria ter, fale com o proprietário ou gerente da sua empresa.',
            icon: LockClosedIcon,
        },
        404: {
            titulo: 'Página não encontrada',
            descricao: 'O endereço que você tentou acessar não existe ou foi movido.',
            icon: MapIcon,
        },
        419: {
            titulo: 'Sessão expirada',
            descricao: 'Sua sessão expirou por inatividade. Faça login novamente para continuar.',
            icon: ClockIcon,
        },
        429: {
            titulo: 'Muitas tentativas',
            descricao: 'Você fez essa ação rápido demais. Espere um instante e tente de novo.',
            icon: ExclamationTriangleIcon,
        },
    };

    return mapa[props.status] ?? {
        titulo: 'Algo deu errado',
        descricao: 'Não foi possível concluir sua solicitação. Tente novamente em alguns instantes.',
        icon: WrenchScrewdriverIcon,
    };
});
</script>

<template>
    <Head :title="conteudo.titulo" />

    <div class="flex min-h-screen items-center justify-center bg-surface-100 px-4">
        <div class="w-full max-w-md text-center">
            <div class="mb-6 flex justify-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-400 text-lg font-bold text-sidebar-950">
                    PA
                </div>
            </div>

            <div class="rounded-2xl border border-surface-200 bg-white p-8">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50">
                    <component :is="conteudo.icon" class="h-6 w-6 text-primary-600" />
                </div>

                <p class="text-xs font-medium uppercase tracking-wide text-sidebar-800/50">Erro {{ status }}</p>
                <h1 class="mt-1 text-lg font-semibold text-sidebar-900">{{ conteudo.titulo }}</h1>
                <p class="mt-2 text-sm text-sidebar-800/70">{{ conteudo.descricao }}</p>

                <Link
                    :href="autenticado ? route('dashboard') : route('login')"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-primary-400 py-2.5 text-sm font-semibold text-sidebar-950 transition-opacity hover:opacity-90"
                >
                    {{ autenticado ? 'Voltar ao painel' : 'Ir para o login' }}
                </Link>
            </div>
        </div>
    </div>
</template>
