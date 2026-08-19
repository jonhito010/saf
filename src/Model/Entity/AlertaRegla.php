<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AlertaRegla extends Entity
{
    protected array $_accessible = [
        'dias_antes' => true,
        'nombre' => true,
        'ambito' => true,
        'tipo_documento' => true,
        'enviar_alerta' => true,
        'ocultar_despues' => true,
        'mantener_visible' => true,
        'nivel_urgencia' => true,
        'activo' => true,
        'orden' => true,
        'empresa_id' => true,
        'created_at' => true,
        'updated_at' => true,
    ];
}
