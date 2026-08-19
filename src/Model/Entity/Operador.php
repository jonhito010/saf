<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Operador extends Entity
{
    protected array $_virtual = ['nombre_completo', 'iniciales', 'foto_url'];

    protected array $_accessible = [
        'empresa_id' => true, 'nombre' => true, 'apellido_paterno' => true,
        'apellido_materno' => true, 'curp' => true, 'telefono' => true,
        'email' => true, 'foto_ruta' => true, 'fecha_ingreso' => true, 'estatus' => true,
        'baja_at' => true, 'baja_motivo' => true, 'baja_usuario_id' => true,
        // Carta Porte 3.1 (FiguraTransporte/Operador)
        'rfc' => true,
        'num_licencia' => true,
        'num_reg_id_trib_figura' => true,
        'residencia_fiscal_figura' => true,
        // Domicilio (FiguraTransporte/Domicilio)
        'domicilio_calle' => true,
        'domicilio_numero_exterior' => true,
        'domicilio_numero_interior' => true,
        'domicilio_colonia' => true,
        'domicilio_localidad' => true,
        'domicilio_referencia' => true,
        'domicilio_municipio' => true,
        'domicilio_estado' => true,
        'domicilio_pais' => true,
        'domicilio_codigo_postal' => true,
    ];

    public function _getNombreCompleto(): string
    {
        $nombre = (string)($this->nombre ?? '');
        $apPat = (string)($this->apellido_paterno ?? '');
        $apMat = (string)($this->apellido_materno ?? '');

        return trim($nombre . ' ' . $apPat . ' ' . $apMat);
    }

    public function _getIniciales(): string
    {
        $nombre = (string)($this->nombre ?? '');
        $apPat = (string)($this->apellido_paterno ?? '');

        if ($nombre === '' && $apPat === '') {
            return '';
        }

        $i = $nombre !== '' ? strtoupper(mb_substr($nombre, 0, 1)) : '';
        $i .= $apPat !== '' ? strtoupper(mb_substr($apPat, 0, 1)) : '';

        return $i;
    }

    protected function _getFotoUrl(): ?string
    {
        $ruta = $this->get('foto_ruta');
        if ($ruta === null || trim((string)$ruta) === '') {
            return null;
        }

        return $ruta;
    }
}
