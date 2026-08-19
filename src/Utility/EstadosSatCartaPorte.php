<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Compatibilidad histórica: algunos flujos usaban claves de 3 letras (Carta Porte).
 * El sistema de ubicaciones usa claves numéricas SAT (01..32).
 */
final class EstadosSatCartaPorte
{
    /** @return array<string,string> letra3 => clave2 */
    public static function letterToKey(): array
    {
        return [
            'AGU' => '01',
            'BCN' => '02',
            'BCS' => '03',
            'CAM' => '04',
            'COA' => '05',
            'COL' => '06',
            'CHP' => '07',
            'CHH' => '08',
            'CMX' => '09',
            'DUR' => '10',
            'GUA' => '11',
            'GRO' => '12',
            'HID' => '13',
            'JAL' => '14',
            'MEX' => '15',
            'MIC' => '16',
            'MOR' => '17',
            'NAY' => '18',
            'NLE' => '19',
            'OAX' => '20',
            'PUE' => '21',
            'QUE' => '22',
            'ROO' => '23',
            'SLP' => '24',
            'SIN' => '25',
            'SON' => '26',
            'TAB' => '27',
            'TAM' => '28',
            'TLA' => '29',
            'VER' => '30',
            'YUC' => '31',
            'ZAC' => '32',
        ];
    }
}
