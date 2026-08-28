<?php

namespace App\Http\Requests;

use App\Models\Recurso;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalvarRecursoRequest extends FormRequest
{
    private const DIAS_SEMANA = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];

    private const MAX_INTERVALOS_POR_DIA = 3;

    public function authorize(): bool
    {
        $recurso = $this->route('recurso');

        return $recurso === null
            ? $this->user()->can('create', Recurso::class)
            : $this->user()->can('update', $recurso);
    }

    public function rules(): array
    {
        $empresaId = Auth::user()->empresa_id;

        $regras = [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in([Recurso::TIPO_ESPACO, Recurso::TIPO_PESSOA])],
            'user_id' => [
                'nullable',
                'prohibited_unless:tipo,'.Recurso::TIPO_PESSOA,
                Rule::exists('users', 'id')->where('empresa_id', $empresaId),
            ],
            'unidade_id' => [
                'required',
                Rule::exists('unidades', 'id')->where('empresa_id', $empresaId),
            ],
            'ativo' => ['boolean'],
            'grade_semanal' => ['nullable', 'array'],
            'servicos' => ['nullable', 'array'],
            'servicos.*' => [
                Rule::exists('servicos', 'id')->where('empresa_id', $empresaId),
            ],
        ];

        // grade_semanal.{dia} é uma lista de intervalos (até 3 — ex. manhã,
        // tarde, noite, ou separados pelo almoço), não um único início/fim.
        foreach (self::DIAS_SEMANA as $dia) {
            $regras["grade_semanal.{$dia}"] = ['array', 'max:'.self::MAX_INTERVALOS_POR_DIA];
            $regras["grade_semanal.{$dia}.*.inicio"] = ['required', 'date_format:H:i'];
            $regras["grade_semanal.{$dia}.*.fim"] = ['required', 'date_format:H:i', 'after:grade_semanal.'.$dia.'.*.inicio'];
        }

        return $regras;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $grade = $this->input('grade_semanal', []);

            foreach (self::DIAS_SEMANA as $dia) {
                $intervalos = $grade[$dia] ?? [];

                if (count($intervalos) < 2) {
                    continue;
                }

                $ordenados = collect($intervalos)->sortBy('inicio')->values();

                for ($i = 0; $i < $ordenados->count() - 1; $i++) {
                    if ($ordenados[$i]['fim'] > $ordenados[$i + 1]['inicio']) {
                        $validator->errors()->add(
                            "grade_semanal.{$dia}",
                            'Os intervalos desse dia não podem se sobrepor.',
                        );

                        break;
                    }
                }
            }
        });
    }
}
