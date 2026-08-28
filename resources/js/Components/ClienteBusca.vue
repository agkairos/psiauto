<script setup lang="ts">
import { ref, watch } from 'vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';

interface Veiculo {
    id: number;
    placa: string;
    marca: { id: number; nome: string } | null;
    modelo: { id: number; nome: string } | null;
}

interface ClienteResultado {
    id: number;
    nome: string;
    telefone: string | null;
    veiculos: Veiculo[];
}

const props = defineProps<{
    modelValue: number | null;
    // Nome do cliente já selecionado (ex.: ao editar um registro existente),
    // pra mostrar no campo sem precisar buscar de novo.
    nomeSelecionado?: string | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    // Dispara com o cliente completo (incluindo veículos) só quando o
    // usuário escolhe um resultado — permite o formulário pai popular o
    // select de veículo sem outra requisição.
    select: [cliente: ClienteResultado | null];
}>();

const termo = ref(props.nomeSelecionado ?? '');
const resultados = ref<ClienteResultado[]>([]);
const buscando = ref(false);
const abertoDropdown = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.nomeSelecionado,
    (nome) => {
        if (nome) termo.value = nome;
    },
);

function aoDigitar() {
    emit('update:modelValue', null);
    emit('select', null);

    if (debounceTimer) clearTimeout(debounceTimer);

    const valor = termo.value.trim();
    if (valor.length < 2) {
        resultados.value = [];
        abertoDropdown.value = false;
        return;
    }

    debounceTimer = setTimeout(async () => {
        buscando.value = true;
        try {
            const resposta = await fetch(`${route('clientes.buscar')}?q=${encodeURIComponent(valor)}`, {
                headers: { Accept: 'application/json' },
            });
            resultados.value = resposta.ok ? await resposta.json() : [];
            abertoDropdown.value = true;
        } finally {
            buscando.value = false;
        }
    }, 300);
}

function selecionar(cliente: ClienteResultado) {
    termo.value = cliente.nome;
    abertoDropdown.value = false;
    resultados.value = [];
    emit('update:modelValue', cliente.id);
    emit('select', cliente);
}

function limpar() {
    termo.value = '';
    resultados.value = [];
    abertoDropdown.value = false;
    emit('update:modelValue', null);
    emit('select', null);
}
</script>

<template>
    <div class="relative">
        <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-sidebar-800/40" />
            <input
                v-model="termo"
                type="text"
                placeholder="Buscar cliente por nome, telefone ou placa…"
                class="w-full rounded-lg border border-surface-200 py-2 pr-8 pl-8 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                @input="aoDigitar"
                @focus="resultados.length > 0 && (abertoDropdown = true)"
            />
            <button
                v-if="termo"
                type="button"
                class="absolute top-1/2 right-2 -translate-y-1/2 text-sidebar-800/40 hover:text-sidebar-800"
                @click="limpar"
            >
                <XMarkIcon class="h-4 w-4" />
            </button>
        </div>

        <div
            v-if="abertoDropdown"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-surface-200 bg-white shadow-lg"
        >
            <button
                v-for="cliente in resultados"
                :key="cliente.id"
                type="button"
                class="flex w-full flex-col items-start px-3 py-2 text-left text-sm hover:bg-surface-50"
                @click="selecionar(cliente)"
            >
                <span class="font-medium text-sidebar-900">{{ cliente.nome }}</span>
                <span class="text-xs text-sidebar-800/60">
                    {{ cliente.telefone ?? 'sem telefone' }}
                    <span v-if="cliente.veiculos.length"> · {{ cliente.veiculos.map((v) => v.placa).join(', ') }}</span>
                </span>
            </button>

            <p v-if="!buscando && resultados.length === 0" class="px-3 py-2 text-xs text-sidebar-800/60">
                Nenhum cliente encontrado.
            </p>
        </div>
    </div>
</template>
