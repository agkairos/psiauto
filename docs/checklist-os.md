# Checklist de entrada da OS (§07)

A especificação funcional não define um schema fixo por segmento — só descreve o
conteúdo genérico do checklist. Decisão de implementação: `checklist_entrada`
(jsonb em `ordens_servico`) usa uma estrutura livre, sem diferenciação obrigatória
por segmento por enquanto. Formato adotado nesta etapa:

```json
{
  "km_entrada": 45210,
  "nivel_combustivel": "1/2",
  "avarias": ["risco na porta traseira direita", "retrovisor esquerdo trincado"],
  "objetos_deixados": "carrinho de bebê no porta-malas",
  "cliente_confirmou": true
}
```

- `avarias` é uma lista de strings (texto livre), não um mapa de pontos numa
  figura do veículo — isso fica para uma iteração futura de UI (desenho do carro
  clicável), quando houver prioridade para isso.
- Sem upload de fotos nesta etapa (§07 pede fotos de antes/depois e no
  checklist) — entra quando o módulo de storage (S3/R2) for conectado.
- Campos por segmento (ex. funilaria pedir algo que mecânica não pede) podem ser
  adicionados livremente dentro desse mesmo jsonb sem migration — é exatamente
  para isso que é jsonb e não colunas fixas (ver CLAUDE.md).
