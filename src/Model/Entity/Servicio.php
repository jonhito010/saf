<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Catálogo de productos/servicios para facturación (SAT).
 *
 * @property int $id
 * @property string $descripcion
 * @property string|null $numeroIdentificacion
 * @property string|null $claveSAT
 * @property string|null|float $precio
 * @property string|null $unidadSAT
 * @property string|null $objetoImp
 * @property int $eliminado
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Servicio extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'descripcion' => true,
        'numeroIdentificacion' => true,
        'claveSAT' => true,
        'precio' => true,
        'unidadSAT' => true,
        'objetoImp' => true,
        'eliminado' => true,
    ];
}
