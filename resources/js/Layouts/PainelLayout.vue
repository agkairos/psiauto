<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    BanknotesIcon,
    BellIcon,
    BuildingStorefrontIcon,
    CalendarDaysIcon,
    CalendarIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    CubeIcon,
    DocumentTextIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    ReceiptPercentIcon,
    TagIcon,
    UserGroupIcon,
    UsersIcon,
    ViewColumnsIcon,
    WrenchScrewdriverIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

interface NavItem {
    label: string;
    href: string;
    icon: typeof HomeIcon;
    /** Some sem essa permissão da lista — omitir para item sempre visível. */
    permissao?: string;
}

interface SharedProps {
    auth: {
        user: { id: number; name: string; email: string } | null;
        empresa: { id: number; nome_fantasia: string; slug: string } | null;
        permissions: string[] | null;
        roles: string[] | null;
    };
    [key: string]: unknown;
}

// Itens de navegação do painel da empresa — um por módulo já mapeado na
// especificação funcional (docs/especificacao-funcional.md). Rotas ainda
// não existem para a maioria; usar href: '#' até o módulo ser implementado.
const todosNavItems: NavItem[] = [
    { label: 'Painel', href: route('dashboard'), icon: HomeIcon },
    { label: 'Agendamentos', href: route('agendamentos.index'), icon: CalendarIcon, permissao: 'agendamentos.ver' },
    { label: 'Recursos e escala', href: route('recursos.index'), icon: CalendarDaysIcon, permissao: 'agenda.gerenciar' },
    { label: 'Painel do dia', href: route('painel-dia.index'), icon: ViewColumnsIcon, permissao: 'agendamentos.ver' },
    { label: 'Ordens de serviço', href: route('ordens-servico.index'), icon: WrenchScrewdriverIcon, permissao: 'os.ver' },
    { label: 'Serviços', href: route('servicos.index'), icon: TagIcon, permissao: 'servicos.ver' },
    { label: 'Clientes', href: route('clientes.index'), icon: UsersIcon, permissao: 'clientes.ver' },
    { label: 'Usuários', href: route('usuarios.index'), icon: UserGroupIcon, permissao: 'usuarios.gerenciar' },
    { label: 'Unidades', href: route('unidades.index'), icon: BuildingStorefrontIcon, permissao: 'empresa.gerenciar' },
    { label: 'Estoque', href: route('produtos.index'), icon: CubeIcon, permissao: 'estoque.ver' },
    { label: 'Financeiro', href: route('financeiro.index'), icon: DocumentTextIcon, permissao: 'financeiro.ver' },
    { label: 'Contas a pagar', href: route('contas-pagar.index'), icon: BanknotesIcon, permissao: 'financeiro.ver' },
    { label: 'Comissões', href: route('comissoes.index'), icon: ReceiptPercentIcon, permissao: 'financeiro.ver' },
    { label: 'Indicadores', href: route('indicadores.index'), icon: ChartBarIcon, permissao: 'indicadores.ver' },
];

const menuAberto = ref(false);
const menuUsuarioAberto = ref(false);

const page = usePage<SharedProps>();

const navItems = computed(() =>
    todosNavItems.filter(
        (item) => !item.permissao || (page.props.auth.permissions ?? []).includes(item.permissao),
    ),
);

// Subconjunto mais usado no dia a dia, para a bottom nav do mobile — o resto
// fica acessível pelo botão "Mais", que abre o drawer com a lista completa.
const navItemsMobile = computed(() => navItems.value.slice(0, 4));

function estaAtivo(item: NavItem): boolean {
    return item.href !== '#' && page.url === item.href;
}

function sair() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="flex min-h-screen bg-surface-100">
        <!-- Sidebar (desktop) -->
        <aside
            class="hidden w-20 shrink-0 flex-col items-center gap-1 bg-sidebar-950 py-4 md:flex"
        >
            <div
                class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-primary-400 font-bold text-sidebar-950"
            >
                PA
            </div>

            <nav class="flex flex-1 flex-col items-center gap-1">
                <a
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    :title="item.label"
                    class="group flex w-16 flex-col items-center gap-1 rounded-lg py-2 transition-colors"
                    :class="
                        estaAtivo(item)
                            ? 'bg-primary-400/15 text-primary-400'
                            : 'text-sidebar-300 hover:bg-sidebar-800 hover:text-primary-400'
                    "
                >
                    <component :is="item.icon" class="h-6 w-6" />
                    <span class="text-[10px] leading-none">{{ item.label.split(' ')[0] }}</span>
                </a>
            </nav>

            <a
                href="#"
                title="Configurações"
                class="flex w-16 flex-col items-center gap-1 rounded-lg py-2 text-sidebar-300 transition-colors hover:bg-sidebar-800 hover:text-primary-400"
            >
                <Cog6ToothIcon class="h-6 w-6" />
                <span class="text-[10px] leading-none">Config</span>
            </a>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top bar -->
            <header
                class="flex items-center gap-3 border-b border-surface-200 bg-surface-50 px-4 py-3 md:px-6"
            >
                <div class="flex items-center gap-2 md:hidden">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-400 text-sm font-bold text-sidebar-950"
                    >
                        PA
                    </div>
                </div>

                <div class="relative hidden flex-1 max-w-md md:block">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-sidebar-800/50"
                    />
                    <input
                        type="search"
                        placeholder="Buscar cliente, veículo, OS…"
                        class="w-full rounded-lg border border-surface-200 bg-white py-2 pr-3 pl-9 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30"
                    />
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <button
                        type="button"
                        class="relative rounded-full p-2 text-sidebar-800 hover:bg-surface-200"
                    >
                        <BellIcon class="h-5 w-5" />
                        <span
                            class="absolute top-1 right-1 h-2 w-2 rounded-full bg-primary-500"
                        />
                    </button>

                    <div class="relative">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-surface-200"
                            @click="menuUsuarioAberto = !menuUsuarioAberto"
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-sidebar-900 text-xs font-semibold text-primary-400"
                            >
                                <BuildingStorefrontIcon class="h-4 w-4" />
                            </div>
                            <span class="hidden flex-col items-start leading-tight sm:flex">
                                <span class="text-sm font-medium text-sidebar-900">
                                    {{ page.props.auth.empresa?.nome_fantasia ?? '—' }}
                                </span>
                                <span class="text-xs text-sidebar-800/60">
                                    {{ page.props.auth.user?.name ?? '' }}
                                </span>
                            </span>
                        </button>

                        <Transition name="fade">
                            <div
                                v-if="menuUsuarioAberto"
                                class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-lg border border-surface-200 bg-white py-1 shadow-lg"
                                @click.self="menuUsuarioAberto = false"
                            >
                                <button
                                    type="button"
                                    class="w-full px-3 py-2 text-left text-sm text-sidebar-900 hover:bg-surface-100"
                                    @click="sair"
                                >
                                    Sair
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <!-- Conteúdo -->
            <main class="flex-1 px-4 pb-24 pt-4 md:px-6 md:pb-6 md:pt-6">
                <slot />
            </main>
        </div>

        <!-- Bottom nav (mobile) -->
        <nav
            class="fixed inset-x-0 bottom-0 z-20 flex items-center justify-around border-t border-surface-200 bg-surface-50 py-1.5 md:hidden"
            style="padding-bottom: max(0.375rem, env(safe-area-inset-bottom))"
        >
            <a
                v-for="item in navItemsMobile"
                :key="item.label"
                :href="item.href"
                class="flex w-14 flex-col items-center gap-0.5 rounded-lg py-1 active:bg-surface-200"
                :class="estaAtivo(item) ? 'text-primary-500' : 'text-sidebar-800'"
            >
                <component :is="item.icon" class="h-6 w-6" />
                <span class="text-[10px] leading-none">{{ item.label.split(' ')[0] }}</span>
            </a>

            <button
                type="button"
                class="flex w-14 flex-col items-center gap-0.5 rounded-lg py-1 text-sidebar-800 active:bg-surface-200"
                @click="menuAberto = true"
            >
                <ViewColumnsIcon class="h-6 w-6" />
                <span class="text-[10px] leading-none">Mais</span>
            </button>
        </nav>

        <!-- Drawer com o menu completo (mobile) -->
        <Transition name="fade">
            <div
                v-if="menuAberto"
                class="fixed inset-0 z-30 bg-sidebar-950/50 md:hidden"
                @click.self="menuAberto = false"
            >
                <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-surface-50 p-4 pb-8">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-sidebar-900">Menu</span>
                        <button type="button" @click="menuAberto = false">
                            <XMarkIcon class="h-5 w-5 text-sidebar-800" />
                        </button>
                    </div>

                    <div class="grid grid-cols-4 gap-3">
                        <a
                            v-for="item in navItems"
                            :key="item.label"
                            :href="item.href"
                            class="flex flex-col items-center gap-1 rounded-lg py-3 text-sidebar-800 active:bg-surface-200"
                            @click="menuAberto = false"
                        >
                            <component :is="item.icon" class="h-6 w-6" />
                            <span class="text-center text-[11px] leading-tight">{{ item.label }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
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
