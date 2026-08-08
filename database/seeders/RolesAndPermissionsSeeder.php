<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Perfis e permissões-base do painel da empresa (§02). A matriz abaixo é um
 * ponto de partida alinhado ao que a especificação funcional diz
 * explicitamente (técnico sem financeiro, atendente sem editar preço) — o
 * resto é uma distribuição razoável, ajustável depois sem migration porque
 * fica todo em banco (Spatie Permission).
 *
 * Convenção de nome: "{modulo}.{acao}".
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            // §01 — cadastro da empresa
            'empresa.gerenciar',

            // §02 — usuários e permissões
            'usuarios.gerenciar',

            // §03 — catálogo de serviços
            'servicos.ver',
            'servicos.editar_preco',
            'servicos.gerenciar',

            // §04 — agenda e escala
            'agenda.gerenciar',

            // §05 — agendamentos
            'agendamentos.ver',
            'agendamentos.gerenciar',

            // §06 — painel do dia e fila
            'painel_dia.ver',

            // §07 — ordem de serviço
            'os.ver',
            'os.gerenciar',

            // §08 — orçamento e aprovação
            'orcamentos.ver',
            'orcamentos.gerenciar',

            // §09 — clientes e veículos
            'clientes.ver',
            'clientes.gerenciar',

            // §10 — lembretes
            'lembretes.gerenciar',

            // §11 — produtos e estoque
            'estoque.ver',
            'estoque.gerenciar',

            // §12 — orçamento de peças
            'orcamento_pecas.gerenciar',

            // §13 — financeiro
            'financeiro.ver',
            'financeiro.gerenciar',

            // §14 — notas fiscais
            'notas_fiscais.gerenciar',

            // §15 — avaliações
            'avaliacoes.ver',
            'avaliacoes.responder',

            // §16 — indicadores
            'indicadores.ver',
        ];

        foreach ($permissoes as $permissao) {
            Permission::findOrCreate($permissao);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $proprietario = Role::findOrCreate('proprietario');
        $proprietario->syncPermissions($permissoes);

        // Gerente: tudo, exceto gerenciar usuários/permissões (fica só com o
        // proprietário) — ajustar se o negócio precisar de outra régua.
        $gerente = Role::findOrCreate('gerente');
        $gerente->syncPermissions(array_diff($permissoes, ['usuarios.gerenciar']));

        $atendente = Role::findOrCreate('atendente');
        $atendente->syncPermissions([
            'agendamentos.ver',
            'agendamentos.gerenciar',
            'painel_dia.ver',
            'os.ver',
            'servicos.ver',
            'clientes.ver',
            'clientes.gerenciar',
            'lembretes.gerenciar',
            'estoque.ver',
            'orcamento_pecas.gerenciar',
            'avaliacoes.ver',
            'avaliacoes.responder',
        ]);

        $tecnico = Role::findOrCreate('tecnico');
        $tecnico->syncPermissions([
            'painel_dia.ver',
            'os.ver',
            'os.gerenciar',
            'servicos.ver',
            'estoque.ver',
            'agendamentos.ver',
        ]);

        $financeiro = Role::findOrCreate('financeiro');
        $financeiro->syncPermissions([
            'financeiro.ver',
            'financeiro.gerenciar',
            'notas_fiscais.gerenciar',
            'indicadores.ver',
            'clientes.ver',
            'os.ver',
        ]);
    }
}
