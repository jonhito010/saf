<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class TipoDocumento extends Entity
{
    protected array $_accessible = [
        'codigo' => true,
        'nombre' => true,
        'ambito' => true,
        'categoria' => true,
        'descripcion' => true,
        'retencion' => true,
        'icono' => true,
        'requerido_normativo' => true,
        'requerido_mp' => true,
        'requerido_cumplimiento' => true,
        'visible_formulario' => true,
        'activo' => true,
        'orden' => true,
        'created_at' => true,
        'updated_at' => true,
    ];
}
