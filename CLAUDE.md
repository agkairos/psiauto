# PsiAuto — Plataforma Integrada de Serviços Automotivos

Marketplace + ERP para oficinas (mecânica, elétrica, funilaria, estética) e casas de
peças, com painel da empresa (web) e aplicativo do cliente (mobile). Multiempresa,
multiunidade, multissegmento.

## Stack

**Backend**
- Laravel 13, PHP 8.3+ (sem `Kernel.php` — configuração em `bootstrap/app.php`)
- PostgreSQL + PostGIS (busca por proximidade), colunas JSONB para checklists
  variáveis por segmento (mecânica/funilaria/estética têm campos diferentes).
  **PostGIS ainda não instalado** no Postgres local — lat/lng ficam por enquanto em
  colunas `double` comuns; migrar para tipo `geography`/PostGIS ao implementar o
  módulo de busca por proximidade (§18).
- Redis — cache, sessões, filas (client `predis`; a extensão nativa `redis`/`phpredis`
  não está disponível no PHP para Windows usado no ambiente de dev local)
- Laravel Horizon — jobs (e-mails, lembretes, geração de PDF, geocoding). **Ainda não
  instalado**: depende de `ext-pcntl`/`ext-posix`, exclusivas de Unix — não existem no
  PHP do Windows. Localmente usamos `php artisan queue:work` para testar filas;
  Horizon (worker + dashboard) só roda dentro de Docker/WSL. Instalar quando o
  ambiente Docker do projeto estiver configurado.
- Laravel Reverb — status da OS e fila em tempo real
- Sanctum — auth web + API do app
- Spatie Permission — perfis/permissões por empresa

**Frontend**
- Inertia 2 (monolito, sem API separada para a web)
- Vue 3 + TypeScript, Composition API com `<script setup>`
- Vite, Tailwind CSS (mobile-first)

**Mobile**
- Capacitor empacotando a app web (não é app nativo separado)
- Recursos nativos obrigatórios: push, câmera (fotos de avaria), ícone/splash próprios

**Infra**
- S3 / Cloudflare R2 — fotos, laudos, anexos
- Google Maps JS + Geocoding — lat/lng gravada no cadastro e cacheada (custo)
- E-mail transacional: Resend, SES ou similar
- Docker + CI/CD
- API REST versionada em `/api/v1`, mantida em paralelo ao Inertia (app + integrações
  futuras)

## Arquitetura e convenções

- **Multiempresa/multiunidade é regra em toda tabela operacional.** Toda query de
  dado operacional (agendamento, OS, orçamento, estoque, financeiro) deve ser
  escopada por `empresa_id` e, quando aplicável, `unidade_id`. Ver skill
  `tenant-scoping`.
- **Checklists variam por segmento** (mecânica ≠ funilaria ≠ estética): modelar como
  JSONB, não como colunas fixas ou tabelas por segmento.
- **Tempo real via Reverb** para: painel do dia/fila, status da OS, notificação de
  orçamento pronto. Ver skill `realtime-status`.
- **Sem venda pela internet de peças** — a consulta de peças mostra disponibilidade e
  gera reserva para retirada presencial, nunca checkout.
- **Toda alteração relevante é auditada** (autor, data, valor anterior) — usuários e
  permissões, preços, aprovações de orçamento.
- **Aprovação de orçamento é item a item**, com registro de quem/quando aprovou
  (app ou assinatura em tela presencial).
- Rotas Inertia servem a web; `/api/v1` serve o app mobile e integrações — não
  duplicar regra de negócio entre os dois, extrair para Actions/Services chamados por
  ambos os controllers.

## Módulos (referência rápida)

**Painel da empresa:** cadastro da empresa, usuários e permissões, catálogo de
serviços, agenda e escala, agendamentos, painel do dia e fila, ordem de serviço,
orçamento e aprovação, clientes e veículos, lembretes, produtos e estoque, orçamento
de peças, financeiro, notas fiscais, avaliações, indicadores.

**App do cliente:** cadastro e garagem, busca e mapa, agendamento, acompanhamento,
consulta e orçamento de peças, avaliação.

**Administração da plataforma:** gestão de empresas e planos, segurança e dados,
acesso e disponibilidade.

A especificação funcional completa (regras de cada módulo) fica fora deste arquivo
para não inflar o contexto por padrão — peça para eu consultar um módulo específico
quando precisar de detalhe, ou veja `docs/especificacao-funcional.md` se você a
colocar lá.

## Skills e agentes deste projeto

- Skill `new-crud-module` — scaffold de um módulo Laravel+Inertia+Vue seguindo os
  padrões acima (migration, model, policy, controller, form requests, página Vue).
- Skill `tenant-scoping` — checklist e padrão de código para isolar dados por
  empresa/unidade.
- Skill `realtime-status` — padrão de broadcast Reverb para mudanças de status de OS
  e fila.
- Agente `psiauto-domain` (`.claude/agents/psiauto-domain.md`) — persona com o
  domínio funcional completo, para tirar dúvidas de regra de negócio ou revisar se
  uma implementação está de acordo com o escopo.
