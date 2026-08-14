/**
 * Máscaras padrão de campo — ver convenção em CLAUDE.md ("Máscaras de campo").
 * Usar sempre com a diretiva `v-maska` (pacote `maska`).
 */
export const MASCARA_CPF = '###.###.###-##';
export const MASCARA_CNPJ = '##.###.###/####-##';
export const MASCARA_CEP = '#####-###';

// Telefone fixo (10 dígitos) e celular (11 dígitos) — maska troca sozinho
// entre as duas ao digitar quando recebe as duas opções separadas por vírgula.
export const MASCARA_TELEFONE = ['(##) ####-####', '(##) #####-####'];

/**
 * CPF ou CNPJ no mesmo campo (ex: cadastro de cliente que aceita PF ou PJ),
 * trocando de máscara automaticamente pela quantidade de dígitos.
 */
export const MASCARA_CPF_CNPJ = [MASCARA_CPF, MASCARA_CNPJ];
