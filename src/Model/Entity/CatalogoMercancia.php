<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class CatalogoMercancia extends Entity
{
    protected array $_accessible = [
        'bienes_transp'           => true,
        'descripcion'             => true,
        'clave_unidad'            => true,
        'peso_en_kg'              => true,
        'material_peligroso'      => true,
        'cve_material_peligroso'  => true,
        'embalaje'                => true,
        'created'                 => true,
        'modified'                => true,
    ];
}
