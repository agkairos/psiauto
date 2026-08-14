<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Painel\AgendamentosController;
use App\Http\Controllers\Painel\AplicacoesProdutoController;
use App\Http\Controllers\Painel\BloqueiosController;
use App\Http\Controllers\Painel\ClientesController;
use App\Http\Controllers\Painel\ComissoesController;
use App\Http\Controllers\Painel\ContasPagarController;
use App\Http\Controllers\Painel\ContasReceberController;
use App\Http\Controllers\Painel\FormasPagamentoController;
use App\Http\Controllers\Painel\IndicadoresController;
use App\Http\Controllers\Painel\ItensOrcamentoController;
use App\Http\Controllers\Painel\MovimentacoesEstoqueController;
use App\Http\Controllers\Painel\OrdensServicoController;
use App\Http\Controllers\Painel\PagamentosContaPagarController;
use App\Http\Controllers\Painel\PainelDiaController;
use App\Http\Controllers\Painel\ProdutosController;
use App\Http\Controllers\Painel\RecebimentosController;
use App\Http\Controllers\Painel\RecursosController;
use App\Http\Controllers\Painel\ServicosController;
use App\Http\Controllers\Painel\UnidadesController;
use App\Http\Controllers\Painel\UsuariosController;
use App\Http\Controllers\Painel\VeiculosController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsuariosController::class, 'index'])->name('index');
        Route::post('/', [UsuariosController::class, 'store'])->name('store');
        Route::put('/{usuario}', [UsuariosController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UsuariosController::class, 'destroy'])->name('destroy');
        Route::post('/{usuario}/reativar', [UsuariosController::class, 'reativar'])->name('reativar');
    });

    Route::prefix('unidades')->name('unidades.')->group(function () {
        Route::get('/', [UnidadesController::class, 'index'])->name('index');
        Route::post('/', [UnidadesController::class, 'store'])->name('store');
        Route::put('/{unidade}', [UnidadesController::class, 'update'])->name('update');
        Route::delete('/{unidade}', [UnidadesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('servicos')->name('servicos.')->group(function () {
        Route::get('/', [ServicosController::class, 'index'])->name('index');
        Route::post('/', [ServicosController::class, 'store'])->name('store');
        Route::put('/{servico}', [ServicosController::class, 'update'])->name('update');
        Route::delete('/{servico}', [ServicosController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('recursos')->name('recursos.')->group(function () {
        Route::get('/', [RecursosController::class, 'index'])->name('index');
        Route::post('/', [RecursosController::class, 'store'])->name('store');
        Route::put('/{recurso}', [RecursosController::class, 'update'])->name('update');
        Route::delete('/{recurso}', [RecursosController::class, 'destroy'])->name('destroy');

        Route::post('/{recurso}/bloqueios', [BloqueiosController::class, 'store'])->name('bloqueios.store');
    });

    Route::delete('bloqueios/{bloqueio}', [BloqueiosController::class, 'destroy'])->name('bloqueios.destroy');

    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClientesController::class, 'index'])->name('index');
        Route::post('/', [ClientesController::class, 'store'])->name('store');
        Route::put('/{cliente}', [ClientesController::class, 'update'])->name('update');
        Route::delete('/{cliente}', [ClientesController::class, 'destroy'])->name('destroy');

        Route::post('/{cliente}/veiculos', [VeiculosController::class, 'store'])->name('veiculos.store');
    });

    Route::put('veiculos/{veiculo}', [VeiculosController::class, 'update'])->name('veiculos.update');
    Route::delete('veiculos/{veiculo}', [VeiculosController::class, 'destroy'])->name('veiculos.destroy');

    Route::prefix('catalogo')->name('catalogo.')->group(function () {
        Route::get('/marcas', [CatalogoController::class, 'marcas'])->name('marcas');
        Route::get('/marcas/{marca}/modelos', [CatalogoController::class, 'modelos'])->name('modelos');
    });

    Route::prefix('agendamentos')->name('agendamentos.')->group(function () {
        Route::get('/', [AgendamentosController::class, 'index'])->name('index');
        Route::post('/', [AgendamentosController::class, 'store'])->name('store');
        Route::put('/{agendamento}', [AgendamentosController::class, 'update'])->name('update');
        Route::patch('/{agendamento}/status', [AgendamentosController::class, 'atualizarStatus'])->name('status');
        Route::post('/{agendamento}/cancelar', [AgendamentosController::class, 'cancelar'])->name('cancelar');
    });

    Route::get('painel-do-dia', [PainelDiaController::class, 'index'])->name('painel-dia.index');

    Route::prefix('ordens-servico')->name('ordens-servico.')->group(function () {
        Route::get('/', [OrdensServicoController::class, 'index'])->name('index');
        Route::post('/', [OrdensServicoController::class, 'store'])->name('store');
        Route::put('/{ordemServico}', [OrdensServicoController::class, 'update'])->name('update');
        Route::patch('/{ordemServico}/status', [OrdensServicoController::class, 'avancarStatus'])->name('status');

        Route::post('/{ordemServico}/itens', [ItensOrcamentoController::class, 'store'])->name('itens.store');
    });

    Route::prefix('itens-orcamento')->name('itens-orcamento.')->group(function () {
        Route::put('/{item}', [ItensOrcamentoController::class, 'update'])->name('update');
        Route::delete('/{item}', [ItensOrcamentoController::class, 'destroy'])->name('destroy');
        Route::post('/{item}/aprovar', [ItensOrcamentoController::class, 'aprovar'])->name('aprovar');
        Route::post('/{item}/recusar', [ItensOrcamentoController::class, 'recusar'])->name('recusar');
    });

    Route::prefix('financeiro')->name('financeiro.')->group(function () {
        Route::get('/', [ContasReceberController::class, 'index'])->name('index');

        Route::post('formas-pagamento', [FormasPagamentoController::class, 'store'])->name('formas-pagamento.store');
        Route::put('formas-pagamento/{formaPagamento}', [FormasPagamentoController::class, 'update'])->name('formas-pagamento.update');
        Route::delete('formas-pagamento/{formaPagamento}', [FormasPagamentoController::class, 'destroy'])->name('formas-pagamento.destroy');

        Route::post('parcelas/{parcela}/recebimentos', [RecebimentosController::class, 'store'])->name('parcelas.recebimentos.store');
    });

    Route::prefix('contas-pagar')->name('contas-pagar.')->group(function () {
        Route::get('/', [ContasPagarController::class, 'index'])->name('index');
        Route::post('/', [ContasPagarController::class, 'store'])->name('store');
        Route::put('/{contaPagar}', [ContasPagarController::class, 'update'])->name('update');
        Route::delete('/{contaPagar}', [ContasPagarController::class, 'destroy'])->name('destroy');

        Route::post('/{contaPagar}/pagamentos', [PagamentosContaPagarController::class, 'store'])->name('pagamentos.store');
    });

    Route::prefix('comissoes')->name('comissoes.')->group(function () {
        Route::get('/', [ComissoesController::class, 'index'])->name('index');
        Route::post('/{comissao}/pagar', [ComissoesController::class, 'marcarPaga'])->name('pagar');
    });

    Route::get('indicadores', [IndicadoresController::class, 'index'])->name('indicadores.index');

    Route::prefix('produtos')->name('produtos.')->group(function () {
        Route::get('/', [ProdutosController::class, 'index'])->name('index');
        Route::post('/', [ProdutosController::class, 'store'])->name('store');
        Route::put('/{produto}', [ProdutosController::class, 'update'])->name('update');
        Route::delete('/{produto}', [ProdutosController::class, 'destroy'])->name('destroy');

        Route::post('/{produto}/movimentacoes', [MovimentacoesEstoqueController::class, 'store'])->name('movimentacoes.store');
        Route::post('/{produto}/aplicacoes', [AplicacoesProdutoController::class, 'store'])->name('aplicacoes.store');
    });

    Route::delete('aplicacoes-produto/{aplicacao}', [AplicacoesProdutoController::class, 'destroy'])->name('aplicacoes-produto.destroy');
});

require __DIR__.'/auth.php';
