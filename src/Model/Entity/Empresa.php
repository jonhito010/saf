<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Empresa extends Entity
{
    protected array $_accessible = [
        'nombre' => true,
        'razon_social' => true,
        'rfc' => true, 'domicilio' => true,
        'colonia' => true, 'municipio' => true, 'estado' => true, 'cp' => true,
        'telefono' => true, 'email' => true,
        'numero_permiso_sict' => true, 'fecha_permiso_sict' => true,
        'vencimiento_permiso_sict' => true, 'estatus' => true,
        'transporte_materiales_peligrosos' => true,
        'folios_disponibles' => true,
        'serie_cfdi' => true,
        'folio_cfdi' => true,
        'lugar_expedicion' => true,
        'folio_inicial' => true,
        'regimen_fiscal' => true,
        'pem_key' => true,
        'pem_cer' => true,
        'numero_certificado' => true,
        'fecha_inicio_cert' => true,
        'fecha_fin_cert' => true,
        'created_by' => true,
        'updated_by' => true,
    ];

    /**
     * Vista y formulario usan `nombre`; en BD puede existir solo `razon_social`.
     * Cake pasa el valor almacenado en `nombre` al getter (o null si no hay columna).
     *
     * @param mixed $nombre Valor crudo del campo `nombre`, si existe.
     */
    protected function _getNombre(mixed $nombre = null): mixed
    {
        if ($nombre !== null && $nombre !== '') {
            return $nombre;
        }

        return $this->get('razon_social');
    }

    public function _getDireccionCompleta(): string
    {
        $parts = array_filter([
            $this->domicilio,
            $this->colonia,
            $this->municipio . ($this->estado ? ', ' . $this->estado : ''),
            $this->cp ? 'C.P. ' . $this->cp : null,
        ]);
        return implode("\n", $parts);
    }
}
