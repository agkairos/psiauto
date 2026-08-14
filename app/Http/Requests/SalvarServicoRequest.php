<?php

namespace App\Http\Requests;

use App\Models\Servico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarServicoRequest extends FormRequest
{
    private const SEGMENTOS = ['mecanica', 'eletrica', 'funilaria', 'estetica', 'pecas'];

    private const CAMPOS_PRECO = ['tipo_preco', 'preco', 'custo', 'comissao_percentual'];

    public function authorize(): bool
    {
        $servico = $this->route('servico');

        return $servico === null
            ? $this->user()->can('create', Servico::class)
            : $this->user()->can('update', $servico);
    }

    public function rules(): array
    {
        $regras = [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'segmento' => ['required', Rule::in(self::SEGMENTOS)],
            'tipo_preco' => ['required', Rule::in(['fixo', 'a_partir_de', 'sob_consulta'])],
            'preco' => ['nullable', 'required_if:tipo_preco,fixo,a_partir_de', 'numeric', 'min:0'],
            'tempo_execucao_minutos' => ['required', 'integer', 'min:1'],
            'garantia_dias' => ['nullable', 'integer', 'min:0'],
            'garantia_km' => ['nullable', 'integer', 'min:0'],
            'comissao_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'custo' => ['nullable', 'numeric', 'min:0'],
            'ativo' => ['boolean'],
        ];

        // Quem só tem servicos.editar_preco (sem servicos.gerenciar) não deve
        // conseguir mudar nome/descrição/segmento/tempo — só os campos de
        // preço. Ver App\Policies\ServicoPolicy::update().
        if ($this->soPodeAlterarPreco()) {
            $regras = array_intersect_key($regras, array_flip([...self::CAMPOS_PRECO, 'ativo']));
        }

        return $regras;
    }

    public function soPodeAlterarPreco(): bool
    {
        return ! $this->user()->can('servicos.gerenciar') && $this->user()->can('servicos.editar_preco');
    }

    /**
     * Dados validados, já filtrados para quem só pode mexer em preço —
     * evita que o controller precise reimplementar essa regra.
     */
    public function dadosParaSalvar(): array
    {
        $dados = $this->validated();

        return $this->soPodeAlterarPreco()
            ? array_intersect_key($dados, array_flip([...self::CAMPOS_PRECO, 'ativo']))
            : $dados;
    }
}
