<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Notificacion extends Entity
{
    protected array $_accessible = [
        'usuario_id' => true,
        'empresa_id' => true,
        'alerta_id' => true,
        'titulo' => true,
        'mensaje' => true,
        'url_destino' => true,
        'tipo' => true,
        'leida' => true,
        'leida_at' => true,
        'created_at' => true,
    ];
}
