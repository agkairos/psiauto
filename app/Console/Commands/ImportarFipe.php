<?php

namespace App\Console\Commands;

use App\Models\Marca;
use App\Models\Modelo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportarFipe extends Command
{
    /**
     * Catálogo global de marcas/modelos (§23), fonte: Tabela FIPE via API
     * pública comunitária (a FIPE não tem API oficial). Ver docs/fipe.md.
     */
    protected $signature = 'fipe:importar {tipo=carros : carros, motos ou caminhoes}';

    protected $description = 'Importa marcas e modelos da Tabela FIPE (via parallelum.com.br/fipe/api)';

    private const BASE_URL = 'https://parallelum.com.br/fipe/api/v1';

    private const TIPO_VEICULO = [
        'carros' => 'carro',
        'motos' => 'moto',
        'caminhoes' => 'caminhao',
    ];

    public function handle(): int
    {
        $tipoApi = $this->argument('tipo');

        if (! isset(self::TIPO_VEICULO[$tipoApi])) {
            $this->error("Tipo inválido. Use: ".implode(', ', array_keys(self::TIPO_VEICULO)));

            return self::FAILURE;
        }

        $tipoVeiculo = self::TIPO_VEICULO[$tipoApi];

        $respostaMarcas = Http::timeout(15)->get(self::BASE_URL."/{$tipoApi}/marcas");

        if ($respostaMarcas->failed()) {
            $this->error('Falha ao buscar marcas na API da FIPE: '.$respostaMarcas->status());

            return self::FAILURE;
        }

        $marcasApi = $respostaMarcas->json();
        $barra = $this->output->createProgressBar(count($marcasApi));
        $barra->start();

        foreach ($marcasApi as $marcaApi) {
            $marca = Marca::updateOrCreate(
                ['tipo_veiculo' => $tipoVeiculo, 'fipe_codigo' => $marcaApi['codigo']],
                ['nome' => $marcaApi['nome']],
            );

            $respostaModelos = Http::timeout(15)
                ->get(self::BASE_URL."/{$tipoApi}/marcas/{$marcaApi['codigo']}/modelos");

            if ($respostaModelos->successful()) {
                foreach ($respostaModelos->json('modelos', []) as $modeloApi) {
                    Modelo::updateOrCreate(
                        ['marca_id' => $marca->id, 'fipe_codigo' => (string) $modeloApi['codigo']],
                        ['nome' => $modeloApi['nome']],
                    );
                }
            }

            // Educado com a API gratuita (500 req/dia sem token).
            usleep(200_000);

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Importação concluída: {$tipoVeiculo}.");

        return self::SUCCESS;
    }
}
