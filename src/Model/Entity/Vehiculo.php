<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Vehiculo extends Entity
{
    protected array $_virtual = ['nombre_completo', 'tipo_carga_label'];

    protected array $_accessible = [
        'empresa_id' => true, 'marca' => true, 'modelo' => true, 'anio' => true,
        'numero_serie' => true, 'numero_motor' => true, 'placas' => true,
        'tipo_carga' => true, 'modalidad' => true, 'tipo_servicio' => true,
        'color' => true, 'peso_bruto_vehicular' => true,
        'num_ejes' => true, 'estatus' => true,
    ];

    public function _getNombreCompleto(): string
    {
        return "{$this->marca} {$this->modelo} {$this->anio}";
    }

    public function _getTipoCargaLabel(): string
    {
        $tipo = $this->tipo_carga;
        if ($tipo === null || $tipo === '') {
            return '';
        }

        return \App\Model\Table\VehiculosTable::tipoCargaOptions()[$tipo] ?? (string)$tipo;
    }
}
