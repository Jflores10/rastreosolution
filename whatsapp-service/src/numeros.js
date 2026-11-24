'use strict';

/**
 * Normaliza un número telefónico al formato internacional sin "+" (ej. 593991234567).
 *
 * Acepta, entre otros:
 *   "0991234567"        -> 593991234567  (formato local con 0)
 *   "991234567"         -> 593991234567  (local sin 0)
 *   "+593 99 123 4567"  -> 593991234567
 *   "593991234567"      -> 593991234567
 *   "5930991234567"     -> 593991234567  (0 sobrante después del código de país)
 *   "00593991234567"    -> 593991234567  (prefijo internacional 00)
 *
 * Devuelve null si el número no parece válido.
 */
function normalizarNumero(entrada, codigoPais = '593') {
    if (entrada === undefined || entrada === null) return null;

    // Solo dígitos
    let n = String(entrada).replace(/\D/g, '');
    if (!n) return null;

    // Prefijo internacional "00"
    if (n.startsWith('00')) n = n.slice(2);

    if (n.startsWith(codigoPais)) {
        // Quitar el 0 de troncal si vino después del código de país (5930991234567)
        const resto = n.slice(codigoPais.length);
        if (resto.startsWith('0')) n = codigoPais + resto.slice(1);
    } else if (n.startsWith('0')) {
        // Formato local: 0 + número
        n = codigoPais + n.slice(1);
    } else if (n.length <= 9) {
        // Número local sin 0 (ej. 991234567)
        n = codigoPais + n;
    }
    // Cualquier otro caso se asume ya internacional (otro país).

    // E.164 permite como máximo 15 dígitos
    if (n.length < 10 || n.length > 15) return null;
    return n;
}

module.exports = { normalizarNumero };
