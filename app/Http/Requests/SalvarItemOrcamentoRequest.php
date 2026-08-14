<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarItemOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A autorização de verdade é sobre a OS pai — o controller já checa
        // Gate::authorize('update', $ordemServico) antes de chegar aqui.
        return true;
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'tipo' => ['required', Rule::in(['servico', 'peca'])],
            'servico_id' => [
                'nullable',
                'required_if:tipo,servico',
                Rule::exists('servicos', 'id')->where('empresa_id', $empresaId),
            ],
            // Nulo = peça em texto livre, sem baixa automática de estoque —
            // continua valendo pra manter compatibilidade com o fluxo antigo.
            'produto_id' => ['nullable', Rule::exists('produtos', 'id')->where('empresa_id', $empresaId)],
            // §07 — "responsável por cada item". Base da comissão (§13).
            'responsavel_id' => ['nullable', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
            'descricao' => ['required', 'string', 'max:255'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
        ];
    }
}
