# Autenticação e login social (Google)

Referência do desenho decidido para o login do painel da empresa. Não cobre o login
do app do cliente (§17) — são fluxos e guards separados.

## Quem loga no painel

Só usuários de empresa (proprietário, gerente, atendente, técnico, financeiro).
Administrador da plataforma (§23-25) e cliente do app (§17) usam rotas/fluxos
próprios, fora deste documento.

## Cadastro (signup) — só o proprietário

- A PsiAuto é um SaaS: a empresa se cadastra sozinha, sem período de teste. No
  momento do cadastro nascem **empresa** e **usuário proprietário** juntos, e o
  acesso é liberado na hora (`situacao_assinatura = ativa`).
- Sem gateway de pagamento integrado ainda (Stripe para cartão, Woovi para Pix) — a
  cobrança de verdade entra depois; até lá o cadastro libera acesso sem cobrar. Ver
  CLAUDE.md.
- Dois caminhos de cadastro, ambos criando proprietário + empresa:
  - **E-mail/senha**: formulário com dados da empresa (razão social, CNPJ,
    segmentos) e do usuário (nome, e-mail, senha).
  - **Google OAuth**: autentica com Google, e-mail já vem verificado; depois do
    callback, cai num formulário para completar os dados da empresa (CNPJ, razão
    social, segmentos) — o Google não fornece isso.
- Modelo comercial é por **licença de usuário** (§02/§23): quanto mais gente da
  empresa usa o sistema, mais a empresa paga — não existe cadastro público de
  "funcionário" se auto-registrando.

## Convite de usuário (gerente/atendente/técnico/financeiro)

- Só nasce por convite de dentro do painel (proprietário ou gerente, conforme
  permissão `usuarios.gerenciar`) — nunca por cadastro público.
- Usuário convidado é criado com `password = null` e um token de convite; usa esse
  token para definir a senha no primeiro acesso, **ou** loga direto via Google se o
  e-mail do convite bater com uma conta Google.
- **Trava de segurança**: login via Google só autentica uma conta que **já existe**
  (`users.email` corresponde a um convite pendente ou usuário ativo daquela
  empresa). Um e-mail Google que não corresponde a nenhum usuário cadastrado nunca
  cria empresa nem usuário automaticamente — isso só acontece no fluxo de signup do
  proprietário.

## Modelo de dados

- `users.password` é nullable — cobre convidado que só usa Google e convite ainda
  não aceito.
- `users.google_id` (nullable, unique) guarda o ID da conta Google vinculada, quando
  houver.
- Um usuário pode ter senha, `google_id`, ou os dois (login por qualquer um dos
  dois métodos deve funcionar se ambos estiverem preenchidos).

## Resumo do fluxo de login (Google)

1. Usuário clica em "Entrar com Google".
2. Callback recebe e-mail + Google ID.
3. Se já existe `users` com esse `google_id` → login direto.
4. Senão, se existe `users` com esse e-mail (convite pendente ou já ativo, sem
   `google_id` ainda) → vincula o `google_id` a essa conta e loga (primeira vez
   usando Google nessa conta).
5. Senão (e-mail não encontrado) → **não cria usuário aqui**. Redireciona para o
   fluxo de signup de empresa, tratando esse Google OAuth como o de um proprietário
   se cadastrando pela primeira vez.
