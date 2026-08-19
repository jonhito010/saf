<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class DocumentoVehiculo extends Entity
{
    private const RUTA_SIN_ARCHIVO = '—';

    protected array $_accessible = ['*' => true];

    protected array $_virtual = ['archivo_url'];

    protected function _getArchivoUrl(): ?string
    {
        $ruta = $this->get('ruta_archivo');
        if ($ruta === null || $ruta === '' || $ruta === self::RUTA_SIN_ARCHIVO) {
            return null;
        }

        return $ruta;
    }
}
