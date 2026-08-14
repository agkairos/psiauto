---
name: new-crud-module
description: Use ao criar um novo módulo/recurso CRUD no painel da empresa da PsiAuto (ex. catálogo de serviços, produtos, clientes e veículos, lembretes). Scaffold consistente de migration, model, policy, form requests, controller Inertia, rota e página Vue seguindo os padrões do projeto (multi-tenant, permissões, auditoria quando aplicável).
---

# Novo módulo CRUD (Laravel 13 + Inertia 2 + Vue 3)

Use este roteiro para qualquer módulo novo do painel da empresa. Antes de começar,
confirme com a skill/agente de domínio (`psiauto-domain`) as regras do módulo
específico — este roteiro cobre só a estrutura técnica.

## 1. Migration

- Nome de tabela em português, snake_case, plural (segue o domínio do documento:
  `servicos`, `veiculos`, `ordens_servico`).
- Sempre incluir `empresa_id` (FK) e `unidade_id` (FK) quando o recurso for por loja
  — ver skill `tenant-scoping`.
- Campos variáveis por segmento (ex. checklist) vão em coluna `jsonb`, não em colunas
  fixas.
- `timestamps()` sempre; `softDeletes()` quando o registro pode ser referenciado por
  histórico (OS, orçamento, cliente, veículo — praticamente tudo operacional).

## 2. Model

- Aplicar `EmpresaScope` (global scope) se o recurso for operacional.
- Declarar `$casts` para os campos jsonb e enums de status.
- Relações explícitas (`belongsTo(Empresa::class)`, etc.) — não confiar em
  convenção implícita quando o nome foge do padrão Laravel.

## 3. Policy

- Uma Policy por model operacional, registrada no `AuthServiceProvider`/
  `bootstrap/app.php` conforme Laravel 13.
- Seguir a matriz de perfis da skill `tenant-scoping` (técnico sem financeiro,
  atendente sem alterar preço, etc.) — não reinventar por módulo.

## 4. Form Requests

- `Store{Recurso}Request` e `Update{Recurso}Request` separados quando as regras
  divergem (ex. campo obrigatório só na criação).
- Validação de negócio que dependa de outro módulo (ex. serviço só pode ser marcado
  em recurso da agenda que o atende) fica em Form Request ou Action, não no
  Controller.

## 5. Controller (Inertia)

- Controller "fino": delega regra de negócio não trivial para uma Action/Service em
  `app/Actions/{Modulo}/` — o mesmo Service deve ser reutilizável pelo controller da
  API `/api/v1` (não duplicar lógica entre web e API, conforme CLAUDE.md).
- `Inertia::render('{Modulo}/Index', [...])` com paginação padrão do projeto; nunca
  retornar coleção inteira sem paginação em módulos com potencial de crescer
  (produtos, clientes, veículos, ordens de serviço).

## 6. Rotas

- Agrupar sob middleware de auth + empresa ativa; usar `Route::resource` quando o
  CRUD é padrão, rotas nomeadas explícitas para ações extras (ex.
  `servicos.duplicar`).

## 7. Página Vue

- `resources/js/Pages/{Modulo}/Index.vue`, `Create.vue`/`Edit.vue` ou um só
  `Form.vue` reutilizado via prop, conforme complexidade do formulário.
- `<script setup lang="ts">`, Composition API, tipos das props batendo com o que o
  controller manda via Inertia.
- Mobile-first com Tailwind — testar em largura estreita antes de considerar
  pronto, já que o painel também roda embrulhado no app via Capacitor em telas
  menores quando aplicável.
- Todo campo de telefone, CPF, CNPJ ou CEP usa `v-maska` com as constantes de
  `resources/js/lib/masks.ts` — sem exceção. Ver "Máscaras de campo" no CLAUDE.md
  (inclui a normalização correspondente no Form Request do backend).

## 8. Depois de criar

- Se o módulo expõe mudança de estado que outra tela precisa ver em tempo real
  (fila, status de OS, orçamento), aplicar a skill `realtime-status`.
- Se o módulo tem campo sensível (preço, custo, permissão), confirmar que fica de
  fora do payload de qualquer evento de broadcast e que a alteração é auditada.
