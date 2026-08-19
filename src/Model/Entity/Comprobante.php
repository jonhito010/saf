<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Comprobante Entity
 *
 * @property int $id
 * @property string|null $nombreEmisor
 * @property string|null $rfcEmisor
 * @property string|null $nombreReceptor
 * @property string|null $rfcReceptor
 * @property string|null $uuid
 * @property string|null $serie
 * @property string|null $folio
 * @property \Cake\I18n\FrozenTime|null $fechaEmision
 * @property string|null $subtotal
 * @property string|null $total
 * @property string|null $descuento
 * @property int|null $estatus
 * @property string|null $xml
 * @property string|null $pdf
 * @property string|null $formaPago
 * @property bool $llevaCartaPorte
 * @property int|null $user_id
 * @property int|null $enviado
 *
 * @property \App\Model\Entity\User $user
 */
class Comprobante extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'nombreEmisor' => true,
        'rfcEmisor' => true,
        'nombreReceptor' => true,
        'rfcReceptor' => true,
        'uuid' => true,
        'serie' => true,
        'folio' => true,
        'fechaEmision' => true,
        'subtotal' => true,
        'total' => true,
        'descuento' => true,
        'estatus' => true,
        'xml' => true,
        'pdf' => true,
        'formaPago' => true,
        'llevaCartaPorte' => true,
        'user_id' => true,
        'enviado' => true,
        'user' => true,
    ];
}
