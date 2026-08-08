# Escopo por empresa vs. por unidade

Referência para toda migration/model: define se o dado operacional é compartilhado
por toda a empresa (todas as lojas) ou é próprio de cada unidade. Baseado na
especificação funcional (`docs/especificacao-funcional.md`). Onde a especificação
não decide isso explicitamente, o item está marcado como **decisão pendente** — não
crie a migration correspondente sem fechar esse ponto antes.

Legenda: **Empresa** = uma linha/config vale para todas as unidades. **Unidade** =
cada loja tem a sua. **Empresa + unidade de origem** = o registro pertence à empresa
como um todo, mas guarda de qual unidade se originou (para relatório/filtro).

| # | Módulo | Escopo | Por quê |
|---|--------|--------|---------|
| 01 | Cadastro da empresa | Empresa (dados cadastrais) / **Unidade** (endereço, localização, horário, estoque) | A spec é explícita: "cada uma [unidade] com endereço, horário e estoque próprios" |
| 02 | Usuários e permissões | Empresa, com `unidade_id` opcional | Usuário pode ser limitado a uma loja ou ter acesso a todas (§02) |
| 03 | Catálogo de serviços | **Empresa** (decisão pendente: preço por unidade?) | Spec não menciona preço variando por loja da mesma empresa; assumir preço único por empresa até confirmar. Se a empresa quiser preço diferente por loja, o preço precisa migrar para Unidade — decidir antes de fixar o schema |
| 04 | Agenda e escala | Unidade | "cada elevador, box, cabine de pintura ou mecânico" é recurso físico de uma loja |
| 05 | Agendamentos | Unidade | Atrelado a um recurso (posição de trabalho) da agenda, que é por unidade |
| 06 | Painel do dia e fila | Unidade | Reflexo em tempo real da operação física de uma loja |
| 07 | Ordem de serviço | Unidade | Execução ocorre fisicamente em uma loja |
| 08 | Orçamento e aprovação | Unidade (via OS) | Vinculado à OS, que é por unidade |
| 09 | Clientes e veículos | **Empresa** | Cliente de balcão pode ser atendido em qualquer loja da mesma empresa; histórico e resumo do cliente (§09) são consolidados na empresa, não travados numa loja. Cada atendimento individual (OS) guarda a unidade de origem |
| 10 | Lembretes | Empresa + unidade de origem | Lembrete nasce de um atendimento (unidade), mas o acompanhamento ("quem retornou") é visão de empresa |
| 11 | Produtos e estoque | Unidade | "Saldo por unidade, quando a empresa tem mais de uma loja" é explícito na spec |
| 12 | Orçamento de peças | Unidade | Depende da disponibilidade/estoque de uma loja específica |
| 13 | Financeiro | Unidade (contas a receber/pagar, caixa por turno) com **consolidação por empresa** | Caixa por turno é físico por loja; fluxo de caixa e indicadores agregam todas as unidades da empresa |
| 14 | Notas fiscais | Unidade (decisão pendente: CNPJ por unidade ou só da matriz?) | Emitida a partir de OS/venda de uma loja; se cada unidade tem CNPJ próprio (filial) isso muda a integração fiscal — decidir antes de implementar emissão |
| 15 | Avaliações | Unidade (vinculada ao atendimento) com exibição agregada na página pública da empresa | Nota exibida "na busca, no mapa e na página da empresa" — a página pública é da unidade (é ela que aparece no mapa, §18), a agregação por empresa é opcional/futura |
| 16 | Indicadores | Empresa, com quebra explícita por unidade | "Faturamento por período, por serviço **e por unidade**" já está na spec |
| 17–22 | App do cliente | Não aplicável (o cliente é da plataforma, não de uma empresa/unidade) | Vínculo com empresa/unidade só existe por atendimento individual |
| 23–25 | Administração da plataforma | Não aplicável (visão global da plataforma) | — |

## Decisões adiadas (default simples por agora, revisitar quando chegarmos no módulo)

Para não travar o início do projeto, ficou definido usar o caminho mais simples em
cada caso abaixo. Isso não é definitivo — é só o que permite começar a codar; cada
item volta à mesa quando o módulo correspondente for implementado, um de cada vez.

1. **Preço de serviço por unidade** (§03) — **default: preço único por empresa**
   (coluna no catálogo da empresa, sem variação por loja). Revisitar ao implementar
   o módulo de catálogo de serviços, se surgir um caso real de empresa que precise
   cobrar diferente entre lojas.
2. **CNPJ por unidade** (§14) — **default: um único CNPJ (matriz) para toda
   emissão fiscal**, mesmo com múltiplas lojas. Revisitar ao implementar o módulo
   de notas fiscais.
3. **Comissão do técnico/vendedor** (§03/§13) — **default: nenhum campo extra**,
   apuração por unidade vem do vínculo usuário→unidade já existente. Confirmar
   quando o módulo financeiro for implementado.

## Implicação para o schema

- Toda tabela marcada **Unidade** tem `unidade_id` not-null (FK) além de
  `empresa_id` (redundante mas evita join extra em toda query, e mantém o
  `EmpresaScope` funcionando mesmo se algum dia `unidade_id` puder ser nulo).
- Toda tabela marcada **Empresa** tem só `empresa_id`; se guardar "unidade de
  origem" (caso de Lembretes, por exemplo), esse campo é nullable e informativo, não
  usado para isolamento de acesso.
- Usuário sem `unidade_id` definido enxerga todas as unidades da empresa (dentro do
  que a permissão dele já libera) — é assim que proprietário/financeiro acessam
  indicadores consolidados.
