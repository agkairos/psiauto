---
name: tenant-scoping
description: Use ao criar ou revisar qualquer model, migration, controller, policy ou query envolvendo dado operacional da PsiAuto (agendamento, OS, orçamento, cliente, veículo, produto, financeiro, nota fiscal). Garante isolamento por empresa/unidade e permissões por perfil. Use também ao investigar um bug onde dados de uma empresa aparecem para outra.
---

# Tenant scoping na PsiAuto

Toda tabela operacional pertence a uma empresa (`empresa_id`) e, quando o recurso é
por loja, a uma unidade (`unidade_id`). Vazamento entre empresas é o bug mais grave
possível neste projeto — trate como P0.

## Migration

- Toda tabela operacional tem `empresa_id` (FK, not null) e, se aplicável,
  `unidade_id` (FK, nullable só quando o recurso realmente não pertence a uma loja
  específica).
- Índice composto `(empresa_id, ...)` nas colunas mais consultadas (ex.:
  `(empresa_id, status)` em agendamentos).

## Model

- Aplicar um Global Scope (`EmpresaScope`) nos models operacionais que filtra
  automaticamente por `empresa_id` da empresa autenticada no contexto (usuário logado
  no painel da empresa). Não confiar em cada controller lembrar de filtrar.
- Em contextos sem empresa autenticada (ex. app do cliente, admin da plataforma),
  usar `withoutGlobalScope(EmpresaScope::class)` explicitamente e escopar manualmente
  pelo critério correto (cliente vê só o que é dele; admin vê tudo).

## Controller / Policy

- Toda Policy deve verificar `empresa_id` do model contra a empresa do usuário
  autenticado antes de checar o perfil (proprietário/gerente/atendente/técnico/
  financeiro) — não basta checar perfil sem checar posse.
- Se o usuário tem `unidade_id` vinculado (não é multi-loja), a Policy também deve
  negar acesso a recursos de outra unidade da mesma empresa.
- Perfis e o que cada um NÃO acessa por padrão (ajustável via Spatie Permission):
  - técnico: sem acesso a financeiro;
  - atendente: sem permissão para alterar preço de serviço/peça;
  - financeiro: acesso amplo ao módulo financeiro, mas não necessariamente à OS
    técnica.

## Auditoria

- Alterações em preço, permissão de usuário e aprovação de orçamento devem gravar
  autor, data/hora e valor anterior (tabela de auditoria ou pacote tipo
  `spatie/laravel-activitylog`). Não é opcional — é requisito de §02 e §08 da
  especificação.

## App do cliente / API

- O cliente da plataforma (`/api/v1`) nunca pertence a uma empresa — os dados dele
  (garagem, histórico) são globais e cada atendimento cria o vínculo com a empresa
  daquele atendimento.
- Histórico de veículo cruzando empresas diferentes só é visível ao cliente se ele
  deu consentimento explícito para compartilhar entre as empresas envolvidas (§24) —
  ao implementar "histórico do veículo" no app, checar esse consentimento antes de
  agregar dados de empresas distintas.

## Checklist antes de dar por pronta uma feature operacional

1. A query teria vazado dado de outra empresa se o Global Scope estivesse desligado?
2. A Policy checa posse (`empresa_id`/`unidade_id`) além do perfil?
3. Se a ação é sensível (preço, permissão, aprovação), ela está sendo auditada?
4. Se o dado toca o app do cliente, o consentimento de compartilhamento foi
   respeitado?
