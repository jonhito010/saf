<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class DocumentoEmpresa extends Entity
{
    /** Marcador en BD cuando aún no hay archivo (mismo valor que DocumentosEmpresaTable::RUTA_SIN_ARCHIVO). */
    private const RUTA_SIN_ARCHIVO = '—';

    protected array $_accessible = ['*' => true];

    protected array $_virtual = ['archivo_url'];

    /**
     * Compatibilidad con vistas y formularios que usan archivo_url (columna real: ruta_archivo).
     */
    protected function _getArchivoUrl(): ?string
    {
        $ruta = $this->get('ruta_archivo');
        if ($ruta === null || $ruta === '' || $ruta === self::RUTA_SIN_ARCHIVO) {
            return null;
        }

        return $ruta;
    }
}
