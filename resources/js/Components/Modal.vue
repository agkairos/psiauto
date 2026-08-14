<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = withDefaults(
    defineProps<{
        open: boolean;
        titulo: string;
        maxWidth?: 'md' | 'lg' | 'xl';
    }>(),
    { maxWidth: 'md' },
);

const emit = defineEmits<{ close: [] }>();

const larguraClasse = {
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
};

// Trava o scroll da página de fundo enquanto o modal está aberto — sem isso
// dava pra rolar a página por trás, além da rolagem do próprio modal.
watch(
    () => props.open,
    (aberto) => {
        document.body.style.overflow = aberto ? 'hidden' : '';
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Transition name="fade">
        <!-- Fecha só pelo X ou pelo botão Cancelar do formulário — clicar
             fora não fecha, pra não perder preenchimento sem querer. -->
        <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center bg-sidebar-950/50 p-4">
            <!--
                max-h-[85vh] + flex-col: o modal nunca ultrapassa o viewport.
                Só o <main> do meio rola; header e footer ficam sempre
                visíveis. É isso que evita a rolagem dupla.
            -->
            <div class="flex max-h-[85vh] w-full flex-col rounded-2xl bg-white" :class="larguraClasse[maxWidth]">
                <header class="flex shrink-0 items-center justify-between border-b border-surface-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-sidebar-900">{{ titulo }}</h2>
                    <button type="button" @click="emit('close')">
                        <XMarkIcon class="h-5 w-5 text-sidebar-800" />
                    </button>
                </header>

                <main class="overflow-y-auto px-6 py-4">
                    <slot />
                </main>

                <footer v-if="$slots.footer" class="shrink-0 border-t border-surface-200 px-6 py-4">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
