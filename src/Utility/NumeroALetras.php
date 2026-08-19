<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Convierte importes numéricos a su representación en letras en español.
 *
 * Soporta hasta 999,999,999,999.99 (novecientos noventa y nueve mil millones…).
 * Sin dependencias externas.
 *
 * Uso:
 *   $n = new NumeroALetras();
 *   echo $n->convertir(1234.56, 'MXN');
 *   // → "MIL DOSCIENTOS TREINTA Y CUATRO PESOS 56/100 MXN"
 */
class NumeroALetras
{
    /** @var array<string, array{singular: string, plural: string}> */
    private array $divisas = [
        'MXN' => ['singular' => 'PESO',              'plural' => 'PESOS'],
        'USD' => ['singular' => 'DÓLAR',             'plural' => 'DÓLARES'],
        'EUR' => ['singular' => 'EURO',              'plural' => 'EUROS'],
        'GBP' => ['singular' => 'LIBRA ESTERLINA',   'plural' => 'LIBRAS ESTERLINAS'],
        'COP' => ['singular' => 'PESO COLOMBIANO',   'plural' => 'PESOS COLOMBIANOS'],
        'ARS' => ['singular' => 'PESO ARGENTINO',    'plural' => 'PESOS ARGENTINOS'],
        'UYU' => ['singular' => 'PESO URUGUAYO',     'plural' => 'PESOS URUGUAYOS'],
        'PEN' => ['singular' => 'SOL',               'plural' => 'SOLES'],
        'CAD' => ['singular' => 'DÓLAR CANADIENSE',  'plural' => 'DÓLARES CANADIENSES'],
        'GTQ' => ['singular' => 'QUETZAL',           'plural' => 'QUETZALES'],
    ];

    // -------------------------------------------------------------------------
    // API pública
    // -------------------------------------------------------------------------

    /**
     * Convierte un importe a letras con formato CFDI.
     *
     * Resultado: "VEINTE PESOS 50/100 MXN"
     *
     * @param  float|int|string $importe  Importe (se trunca a 2 decimales).
     * @param  string           $moneda   Código ISO 4217 (MXN, USD, EUR…).
     * @return string
     */
    public function convertir($importe, string $moneda = 'MXN'): string
    {
        $importe  = (float) $importe;
        $negativo = $importe < 0;
        $importe  = abs($importe);

        // Separar entero y decimales (2 dígitos)
        $entero    = (int) floor($importe);
        $centavos  = (int) round(($importe - $entero) * 100);
        if ($centavos >= 100) {           // redondeo que sube el entero
            $entero++;
            $centavos = 0;
        }

        $letrasEntero = $this->enLetras($entero);

        // Nombre de la divisa
        $divisa = $this->divisas[$moneda] ?? ['singular' => $moneda, 'plural' => $moneda];
        $nombreDivisa = $entero === 1 ? $divisa['singular'] : $divisa['plural'];

        // Centavos con dos dígitos
        $cts = str_pad((string) $centavos, 2, '0', STR_PAD_LEFT);

        $resultado = ($negativo ? 'MENOS ' : '') . $letrasEntero . ' ' . $nombreDivisa . ' ' . $cts . '/100 ' . $moneda;

        return trim(preg_replace('/\s+/', ' ', $resultado));
    }

    /**
     * Solo la parte entera en letras (sin divisa ni centavos).
     *
     * @param  int $numero
     * @return string
     */
    public function enLetras(int $numero): string
    {
        if ($numero === 0) {
            return 'CERO';
        }

        $partes = [];

        // Miles de millones (hasta 999 999)
        $milesMillones = (int) floor($numero / 1_000_000_000);
        if ($milesMillones > 0) {
            $partes[] = $this->grupo($milesMillones) . ' MIL MILLONES';
            $numero  %= 1_000_000_000;
        }

        // Millones
        $millones = (int) floor($numero / 1_000_000);
        if ($millones > 0) {
            $partes[] = $this->grupo($millones) . ($millones === 1 ? ' MILLÓN' : ' MILLONES');
            $numero  %= 1_000_000;
        }

        // Miles
        $miles = (int) floor($numero / 1_000);
        if ($miles > 0) {
            // "MIL" a secas (sin "UN MIL")
            $partes[] = ($miles === 1 ? '' : $this->grupo($miles) . ' ') . 'MIL';
            $numero  %= 1_000;
        }

        // Resto (0-999)
        if ($numero > 0) {
            $partes[] = $this->grupo($numero);
        }

        return implode(' ', array_filter($partes, fn($p) => $p !== ''));
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    /**
     * Convierte un número de 1 a 999 en letras.
     */
    private function grupo(int $n): string
    {
        $resultado = '';

        // Centenas
        $centenas = (int) floor($n / 100);
        $resto    = $n % 100;

        if ($centenas > 0) {
            if ($centenas === 1) {
                $resultado .= ($resto === 0) ? 'CIEN' : 'CIENTO';
            } else {
                $resultado .= $this->centenas($centenas);
            }
        }

        if ($resto > 0) {
            $resultado .= ($resultado !== '' ? ' ' : '') . $this->decenas($resto);
        }

        return $resultado;
    }

    /**
     * Centenas: 2–9 (la de 1 se maneja en grupo()).
     */
    private function centenas(int $c): string
    {
        return match ($c) {
            2 => 'DOSCIENTOS',
            3 => 'TRESCIENTOS',
            4 => 'CUATROCIENTOS',
            5 => 'QUINIENTOS',
            6 => 'SEISCIENTOS',
            7 => 'SETECIENTOS',
            8 => 'OCHOCIENTOS',
            9 => 'NOVECIENTOS',
            default => '',
        };
    }

    /**
     * Decenas + unidades: 1–99.
     */
    private function decenas(int $n): string
    {
        // 1–29: palabras únicas o compuestas sin "Y"
        if ($n <= 29) {
            return $this->menoresDeTreinta($n);
        }

        $decena = (int) floor($n / 10);
        $unidad = $n % 10;

        $nombreDecena = match ($decena) {
            3 => 'TREINTA',
            4 => 'CUARENTA',
            5 => 'CINCUENTA',
            6 => 'SESENTA',
            7 => 'SETENTA',
            8 => 'OCHENTA',
            9 => 'NOVENTA',
            default => '',
        };

        if ($unidad === 0) {
            return $nombreDecena;
        }

        return $nombreDecena . ' Y ' . $this->unidades($unidad);
    }

    /**
     * Números del 1 al 29.
     */
    private function menoresDeTreinta(int $n): string
    {
        return match ($n) {
            1  => 'UN',
            2  => 'DOS',
            3  => 'TRES',
            4  => 'CUATRO',
            5  => 'CINCO',
            6  => 'SEIS',
            7  => 'SIETE',
            8  => 'OCHO',
            9  => 'NUEVE',
            10 => 'DIEZ',
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            20 => 'VEINTE',
            21 => 'VEINTIÚN',
            22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE',
            default => '',
        };
    }

    /**
     * Unidades simples: 1–9.
     */
    private function unidades(int $n): string
    {
        return match ($n) {
            1 => 'UNO',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            default => '',
        };
    }
}
