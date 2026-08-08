# Especificação Funcional — Plataforma Integrada de Serviços Automotivos

Segmentos: mecânica geral, elétrica automotiva, funilaria, estética automotiva,
casas de peças.

## Painel da empresa

### 01 Cadastro da empresa
Identidade da empresa dentro da plataforma.
- Dados cadastrais: razão social, nome fantasia, CNPJ, contato e logotipo.
- Segmentos atendidos: mecânica, elétrica, funilaria, estética ou peças, podendo
  marcar mais de um.
- Endereço e localização: o endereço é convertido em ponto no mapa, usado na busca
  por proximidade.
- Horário de funcionamento: por dia da semana, com intervalo de almoço.
- Unidades: uma empresa pode ter mais de uma loja, cada uma com endereço, horário e
  estoque próprios.
- Página pública: fotos, descrição, serviços, preços e avaliações, visível no
  aplicativo do cliente.

### 02 Usuários e permissões
Quem acessa o sistema e o que cada um pode fazer.
- Perfis disponíveis: proprietário, gerente, atendente, técnico e financeiro.
- Restrição por função: o técnico não vê o financeiro, o atendente não altera preço,
  e assim por diante.
- Vínculo com unidade: o usuário pode ser limitado a uma loja específica.
- Registro de ações: toda alteração relevante fica gravada com autor, data e valor
  anterior.

### 03 Catálogo de serviços
O que a empresa vende e em quanto tempo entrega.
- Lista de serviços selecionada a partir de um catálogo padrão da plataforma, o que
  permite comparar preços entre empresas.
- Preço: valor fixo, valor a partir de, ou sob consulta.
- Tempo de execução: define quanto o serviço ocupa na agenda.
- Garantia: prazo em dias e limite de quilometragem.
- Comissão: percentual pago ao técnico ou ao vendedor naquele serviço.
- Custo: usado no cálculo de margem.

### 04 Agenda e escala
Define quantos veículos a empresa consegue atender e quando.
- Recursos de atendimento: cada elevador, box, cabine de pintura ou mecânico é
  cadastrado como uma posição de trabalho.
- Grade semanal: horários de início e fim por dia da semana, para cada recurso.
- Serviços por recurso: define o que cada posição atende, evitando marcar pintura em
  box de mecânica.
- Bloqueios: feriado, férias, manutenção do equipamento ou qualquer indisponibilidade.
- Encaixe: horário extra fora da grade, quando a empresa quiser abrir exceção.
- Liberação automática: concluído o serviço, o horário volta a ficar disponível para
  novos agendamentos.
- Antecedência mínima: impede marcação em cima da hora.
- O sistema impede que dois veículos ocupem a mesma posição no mesmo horário.

### 05 Agendamentos
Recebimento e controle das marcações.
- Origem: marcação feita pelo cliente no aplicativo ou lançada pela própria empresa
  no balcão.
- Situações: solicitado, confirmado, veículo recebido, em execução, concluído,
  cancelado ou não compareceu.
- Confirmação: a empresa aceita ou recusa a solicitação; o cliente é avisado.
- Remarcação e cancelamento com registro de motivo.
- Lista de espera: quando não há horário, o cliente entra na fila e é avisado se
  surgir vaga.
- Visualização: calendário por dia, semana ou recurso.
- Lembrete de véspera enviado automaticamente ao cliente.

### 06 Painel do dia e fila
Situação da operação em tempo real.
- Quadro do dia com todos os veículos, separados por etapa do atendimento.
- Fila por posição: mostra quantos veículos estão aguardando cada box ou mecânico.
- Ordem de atendimento ajustável pela empresa.
- Tempo estimado calculado a partir da duração dos serviços já em execução.
- Atualização imediata: qualquer mudança aparece na hora para a equipe e para o
  cliente.

### 07 Ordem de serviço
O registro completo do atendimento, do recebimento à entrega.
- Checklist de entrada: quilometragem, nível de combustível, avarias marcadas na
  figura do veículo, fotos e objetos deixados no carro, com confirmação do cliente na
  tela.
- Reclamação do cliente: o que ele relatou, nas palavras dele.
- Diagnóstico técnico: o que foi identificado pela equipe.
- Serviços e peças aplicados, com responsável por cada item.
- Etapas do atendimento: aguardando aprovação, em execução, aguardando peça, em
  teste, pronto, entregue.
- Recomendações: o que ficou pendente e deve ser feito depois, gerando lembrete
  futuro.
- Fotos de antes e depois, especialmente úteis em funilaria e estética.
- Laudo final em PDF com tudo o que foi executado, enviado ao cliente por e-mail.
- Garantia emitida por serviço e por peça, com prazo e quilometragem.
- Retorno de garantia: a ordem é marcada como retrabalho e vinculada ao atendimento
  original.
- Quilometragem de saída registrada na entrega.

### 08 Orçamento e aprovação
Autorização do cliente antes da execução.
- Montagem do orçamento com serviços, peças, quantidades, valores e descontos.
- Envio ao cliente pelo aplicativo e por e-mail.
- Aprovação item a item: o cliente pode autorizar parte dos itens e recusar outros.
- Registro da autorização: data, hora e identificação de quem aprovou.
- Validade do orçamento e reajuste quando expirado.
- Aprovação presencial com assinatura na tela, para o cliente que está na loja.

### 09 Clientes e veículos
Base de relacionamento da empresa.
- Cadastro de cliente feito pela empresa, para o cliente de balcão que não usa o
  aplicativo.
- Cliente da plataforma: quem se cadastrou sozinho e escolheu a empresa; o vínculo é
  criado no primeiro atendimento.
- Convite: a empresa pode convidar o cliente de balcão a criar conta e passar a
  acompanhar pelo aplicativo.
- Veículos: placa, chassi, marca, modelo, ano, versão, cor e quilometragem atual.
- Histórico por veículo: todos os atendimentos, com data, serviços, peças e valores.
- Resumo por cliente: quantas vezes atendeu, quanto gastou e quando foi a última
  visita.
- Observações internas visíveis apenas para a equipe.

### 10 Lembretes
Avisos automáticos de retorno.
- Por data ou quilometragem: o sistema estima a média rodada por mês e calcula
  quando avisar.
- Tipos previstos: troca de óleo, revisão, alinhamento, higienização do
  ar-condicionado, serviços de estética e vencimento de garantia.
- A partir da recomendação: o que ficou pendente na ordem de serviço vira um
  lembrete futuro.
- Lembrete manual criado pela empresa para qualquer situação.
- Envio por e-mail e por aviso no aplicativo.
- Acompanhamento: a empresa vê quem foi avisado e quem retornou.

### 11 Produtos e estoque
Controle de peças e materiais.
- Cadastro de produto: código, código de barras, nome, marca, unidade, custo, preço
  de venda e fotos.
- Aplicação por veículo: em quais marcas, modelos e anos a peça serve.
- Saldo por unidade, quando a empresa tem mais de uma loja.
- Estoque mínimo com alerta de reposição.
- Entrada por nota de compra, com fornecedor e atualização do custo.
- Movimentações registradas: entrada, saída, ajuste, reserva e perda.
- Baixa automática quando a peça é usada em uma ordem de serviço.
- Visibilidade: a empresa escolhe quais produtos aparecem na consulta do cliente.
- Não há venda pela internet. A consulta mostra apenas disponibilidade; a retirada é
  presencial.

### 12 Orçamento de peças
Pedidos de preço recebidos dos clientes.
- Recebimento do pedido com a lista de itens e o veículo do cliente.
- Prazo de entrega para itens que precisam ser encomendados.
- Precificação item a item, indicando se está em estoque, se precisa encomendar ou
  se não há disponibilidade.
- Aviso automático por e-mail quando o orçamento fica pronto.
- Validade do valor informado.
- Reserva para retirada: a peça fica separada por um prazo determinado.
- Conversão: o orçamento aprovado pode virar venda ou entrar direto em uma ordem de
  serviço.

### 13 Financeiro
Controle do dinheiro que entra e do que sai.
- Contas a receber: geradas automaticamente pela ordem de serviço ou pela venda de
  peça, com parcelamento e forma de pagamento.
- Baixa de recebimento total ou parcial, com controle de atraso.
- Contas a pagar: fornecedores, despesas fixas e despesas recorrentes, com alerta de
  vencimento.
- Fluxo de caixa por período, com saldo atual e saldo projetado.
- Caixa por turno: abertura com valor inicial, sangrias, suprimentos e fechamento com
  conferência e diferença apurada.
- Formas de pagamento configuráveis, com taxa e prazo de recebimento de cartão.
- Comissões calculadas por serviço ou por peça, com relatório por funcionário e
  controle de pagamento.
- Margem por atendimento: receita menos custo de peça e comissão, mostrando quanto
  sobrou em cada ordem.
- Centro de custo e classificação de receitas e despesas.

### 14 Notas fiscais
Emissão dos documentos fiscais da operação.
- Nota fiscal de serviço emitida a partir da ordem de serviço concluída.
- Nota fiscal de venda de peça emitida na saída do produto.
- Envio ao cliente por e-mail e disponível no aplicativo.
- Controle de situação: pendente, emitida, cancelada ou com erro.
- Arquivo guardado junto ao atendimento.

### 15 Avaliações
Reputação da empresa dentro da plataforma.
- Somente cliente atendido avalia: a avaliação é liberada após a conclusão do
  atendimento.
- Nota geral de 1 a 5 e comentário livre.
- Notas por critério: qualidade do serviço, preço e cumprimento do prazo.
- Resposta pública da empresa a cada avaliação.
- Média exibida na busca, no mapa e na página da empresa.

### 16 Indicadores
Painel gerencial do proprietário.
- Faturamento por período, por serviço e por unidade.
- Ticket médio por atendimento.
- Conversão de orçamentos em serviços executados.
- Ocupação da agenda: quanto da capacidade foi realmente utilizada.
- Tempo médio de permanência do veículo na oficina.
- Retorno de clientes em 6 e 12 meses.
- Produção por técnico e taxa de retrabalho.
- Giro de estoque e produtos parados.

## Aplicativo do cliente

### 17 Cadastro e garagem
Conta do motorista.
- Cadastro próprio com e-mail e telefone; o cliente pertence à plataforma, não a uma
  empresa.
- Garagem: vários veículos na mesma conta, cada um com dados e histórico próprios.
- Atualização de quilometragem informada pelo cliente ou registrada nos atendimentos.
- Dados pessoais editáveis, com controle de quais informações são compartilhadas.

### 18 Busca e mapa
Como o cliente encontra a empresa.
- Mapa por proximidade a partir da localização atual ou de um endereço informado.
- Filtros: tipo de serviço, segmento, distância, nota mínima e empresas abertas no
  momento.
- Página da empresa: serviços, preços, fotos, horário, avaliações e rota até o local.
- Valor de referência: preço médio praticado para aquele serviço na região.

### 19 Agendamento
Marcação do serviço.
- Escolha do serviço e do veículo da garagem.
- Horários disponíveis exibidos conforme a escala real da empresa.
- Observações do cliente sobre o problema.
- Confirmação, lembrete de véspera e opção de cancelar ou remarcar.
- Meus agendamentos: próximos atendimentos e histórico.

### 20 Acompanhamento
O que acontece com o veículo.
- Posição na fila e quantos veículos estão à frente.
- Etapa atual: recebido, aguardando aprovação, em execução, aguardando peça, pronto
  para retirada.
- Aprovação do orçamento pelo próprio aplicativo, item a item.
- Fotos registradas na entrada e na conclusão.
- Laudo e nota fiscal disponíveis para download.
- Histórico do veículo reunindo os atendimentos feitos em diferentes empresas.
- Lembretes de manutenção e garantias vigentes.

### 21 Consulta e orçamento de peças
Peças sem sair de casa.
- Busca de peça com filtro pelo veículo cadastrado na garagem.
- Disponibilidade informada por cada loja.
- Solicitação de orçamento com um ou mais itens.
- Aviso por e-mail quando o orçamento fica pronto.
- Confirmação de interesse e reserva da peça para retirada presencial.

### 22 Avaliação
Retorno sobre o atendimento.
- Convite automático enviado após a conclusão do serviço.
- Nota e comentário, com avaliação separada de qualidade, preço e prazo.
- Resposta da empresa visível junto à avaliação.

## Administração da plataforma

### 23 Gestão de empresas e planos
Controle interno da operação.
- Aprovação de novos cadastros de empresas antes da publicação no mapa.
- Planos de assinatura com limites de usuários, unidades e volume de atendimentos.
- Situação da assinatura: em teste, ativa, em atraso ou cancelada.
- Destaque no mapa como recurso adicional contratável.
- Catálogo global de serviços e de marcas e modelos de veículos.
- Acompanhamento de uso por empresa: agendamentos, ordens de serviço e acessos.

### 24 Segurança e dados
Proteção das informações de empresas e clientes.
- Separação por empresa: cada empresa acessa apenas os próprios dados.
- Consentimento do cliente para compartilhar o histórico do veículo entre empresas
  diferentes.
- Registro de ações dos usuários, com autor, data e alteração realizada.
- Exportação de dados da empresa a qualquer momento.
- Exclusão de dados pessoais mediante solicitação do titular.
- Cópia de segurança periódica das informações.

### 25 Acesso e disponibilidade
Como a plataforma é utilizada.
- Uso pelo navegador em computador, tablet e celular, com telas adaptadas ao tamanho
  do aparelho.
- Aplicativo publicado na App Store e no Google Play.
- Avisos no aparelho para agendamentos, orçamentos prontos e avisos de orçamento.
- Uso da câmera do celular para o registro fotográfico de entrada e conclusão.
- Comunicação por e-mail para confirmações, laudos e avisos de orçamento.
