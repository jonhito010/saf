<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Cliente Entity
 *
 * @property int $id
 * @property string|null $rfc
 * @property string|null $nombre
 * @property string|null $regimenFiscal
 * @property string|null $usoCFDI
 * @property string|null $domicilioFiscal
 * @property string|null $correo
 * @property string|null $direccionCompleta
 * @property int|null $eliminado
 */
class Cliente extends Entity
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
        'rfc' => true,
        'nombre' => true,
        'regimenFiscal' => true,
        'usoCFDI' => true,
        'domicilioFiscal' => true,
        'correo' => true,
        'direccionCompleta' => true,
        'eliminado' => true,
    ];
}
