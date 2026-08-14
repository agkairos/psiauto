<?php

namespace App\Support;

class ApenasNumeros
{
    /**
     * Remove tudo que não for dígito. Usar sempre em campos que chegam
     * mascarados do front (CPF, CNPJ, telefone, CEP) antes de validar/salvar
     * — ver convenção "Máscaras de campo" em CLAUDE.md.
     */
    public static function de(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return preg_replace('/\D/', '', $valor) ?? '';
    }
}
