<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Ubicacion extends Entity
{
    protected array $_accessible = [
        'tipo_ubicacion' => true,
        'id_ubicacion' => true,
        'rfc' => true,
        'nombre' => true,
        'calle' => true,
        'numero_exterior' => true,
        'numero_interior' => true,
        'colonia' => true,
        'municipio' => true,
        'estado' => true,
        'codigo_postal' => true,
        'localidad' => true,
        'pais' => true,
        'referencia' => true,
        'created' => true,
        'modified' => true,
    ];
}

