<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

class Alerta extends Entity
{
    protected array $_accessible = ['*' => true];

    /** Compatibilidad con vistas que usan titulo / tipo / created */
    protected array $_virtual = ['titulo', 'tipo', 'created'];

    protected function _getTitulo(): string
    {
        return (string)($this->descripcion ?? '');
    }

    protected function _getTipo(): string
    {
        return (string)($this->nivel_urgencia ?? 'info');
    }

    protected function _getCreated(): ?DateTime
    {
        $v = $this->created_at ?? null;
        if ($v === null) {
            return null;
        }

        return $v instanceof DateTime ? $v : new DateTime($v);
    }
}
