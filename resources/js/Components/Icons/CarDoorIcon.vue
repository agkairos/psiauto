<script setup lang="ts">
// Ícone customizado (porta de carro) — não existe no Heroicons. Mesmo estilo
// outline/stroke-1.5 do resto do set, pra ficar consistente visualmente.
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M5.25 20.25V6.75a1.5 1.5 0 0 1 1.5-1.5h6.44a1.5 1.5 0 0 1 1.06.44l4.06 4.06a1.5 1.5 0 0 1 .44 1.06v9.44a.75.75 0 0 1-.75.75H6a.75.75 0 0 1-.75-.75Z"
        />
        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 9.75h13.5" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 14.25h1.5" />
    </svg>
</template>
