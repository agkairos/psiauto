<?php

namespace App\Http\Requests;

use App\Models\ContaPagar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarContaPagarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contaPagar = $this->route('contaPagar');

        return $contaPagar === null
            ? $this->user()->can('create', ContaPagar::class)
            : $this->user()->can('update', $contaPagar);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        return [
            'unidade_id' => ['nullable', Rule::exists('unidades', 'id')->where('empresa_id', $empresaId)],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
            'recorrente' => ['boolean'],
            'periodicidade' => ['nullable', Rule::in(['mensal']), 'required_if:recorrente,1'],
        ];
    }
}
