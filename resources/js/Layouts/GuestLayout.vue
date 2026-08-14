<script setup lang="ts"></script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-surface-100 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-400 text-lg font-bold text-sidebar-950"
                >
                    PA
                </div>
            </div>

            <div class="rounded-2xl border border-surface-200 bg-white p-6 shadow-sm sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
