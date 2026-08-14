<script setup lang="ts">
import PainelLayout from '@/Layouts/PainelLayout.vue';
</script>

<template>
    <PainelLayout>
        <h1 class="text-xl font-semibold text-sidebar-900">Painel</h1>
        <p class="mt-1 text-sm text-sidebar-800/70">
            Base visual da PsiAuto — conteúdo real de cada módulo entra aqui.
        </p>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in ['Agendamentos hoje', 'Em execução', 'Aguardando aprovação', 'Prontos para retirada']"
                :key="card"
                class="rounded-xl border border-surface-200 bg-white p-4"
            >
                <p class="text-xs font-medium text-sidebar-800/60">{{ card }}</p>
                <p class="mt-2 text-2xl font-semibold text-sidebar-900">—</p>
            </div>
        </div>
    </PainelLayout>
</template>
