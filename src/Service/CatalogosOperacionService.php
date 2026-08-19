<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

/**
 * Catálogos de operación (selects) desde BD, con fallback estático.
 */
class CatalogosOperacionService
{
    /** @var array<string, string>|null */
    private static ?array $tipoCargaCache = null;

    /** @var array<string, string>|null */
    private static ?array $modalidadCache = null;

    /** @var array<string, array<string, string>>|null */
    private static ?array $tipoServicioPorModalidadCache = null;

    public static function limpiarCache(): void
    {
        self::$tipoCargaCache = null;
        self::$modalidadCache = null;
        self::$tipoServicioPorModalidadCache = null;
    }

    /** @return array<string, string> codigo => nombre */
    public function tipoCargaOptions(): array
    {
        if (self::$tipoCargaCache !== null) {
            return self::$tipoCargaCache;
        }

        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT codigo, nombre FROM catalogo_tipo_carga WHERE activo = 1 ORDER BY orden ASC, nombre ASC',
            )->fetchAll('assoc');
            if ($rows !== []) {
                $out = [];
                foreach ($rows as $row) {
                    $out[(string)$row['codigo']] = (string)$row['nombre'];
                }
                self::$tipoCargaCache = $out;

                return $out;
            }
        } catch (\Throwable) {
            // fallback
        }

        self::$tipoCargaCache = [
            'carga_general' => 'Carga general',
            'carga_especializada' => 'Carga especializada',
            'material_peligroso' => 'Material peligroso',
            'voluminosos' => 'Voluminosos',
            'fondos_y_valores' => 'Fondos y valores',
        ];

        return self::$tipoCargaCache;
    }

    /** @return array<string, string> */
    public function modalidadOptions(): array
    {
        if (self::$modalidadCache !== null) {
            return self::$modalidadCache;
        }

        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT codigo, nombre FROM catalogo_modalidad WHERE activo = 1 ORDER BY orden ASC, nombre ASC',
            )->fetchAll('assoc');
            if ($rows !== []) {
                $out = [];
                foreach ($rows as $row) {
                    $out[(string)$row['codigo']] = (string)$row['nombre'];
                }
                self::$modalidadCache = $out;

                return $out;
            }
        } catch (\Throwable) {
            // fallback
        }

        self::$modalidadCache = [
            'AUTOTRANSPORTE FEDERAL' => 'Transporte federal',
            'TRANSPORTE PRIVADO' => 'Transporte privado',
        ];

        return self::$modalidadCache;
    }

    /** @return array<string, string> */
    public function tipoServicioOptions(?string $modalidad = null): array
    {
        $porModalidad = $this->tipoServicioPorModalidad();
        if ($modalidad !== null && $modalidad !== '') {
            return $porModalidad[$modalidad] ?? [];
        }

        $all = [];
        foreach ($porModalidad as $opts) {
            foreach ($opts as $codigo => $nombre) {
                $all[$codigo] = $nombre;
            }
        }

        return $all;
    }

    /** @return array<string, array<string, string>> modalidad => [codigo => nombre] */
    public function tipoServicioPorModalidad(): array
    {
        if (self::$tipoServicioPorModalidadCache !== null) {
            return self::$tipoServicioPorModalidadCache;
        }

        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT codigo, modalidad_codigo, nombre
                 FROM catalogo_tipo_servicio
                 WHERE activo = 1
                 ORDER BY orden ASC, nombre ASC',
            )->fetchAll('assoc');
            if ($rows !== []) {
                $out = [];
                foreach ($rows as $row) {
                    $mod = (string)$row['modalidad_codigo'];
                    $out[$mod][(string)$row['codigo']] = (string)$row['nombre'];
                }
                // Asegurar claves de modalidades sin servicios
                foreach (array_keys($this->modalidadOptions()) as $modCodigo) {
                    $out[$modCodigo] = $out[$modCodigo] ?? [];
                }
                self::$tipoServicioPorModalidadCache = $out;

                return $out;
            }
        } catch (\Throwable) {
            // fallback
        }

        $federal = [
            'CARGA GENERAL' => 'Carga general',
            'PAQUETERIA Y MENSAJERIA' => 'Paquetería y mensajería',
            'ARRENDAMIENTO' => 'Arrendamiento',
            'CARGA ESPECIALIZADA' => 'Carga especializada',
            'AUTOTRANSPORTE FEDERAL' => 'Autotransporte federal',
        ];
        self::$tipoServicioPorModalidadCache = [
            'AUTOTRANSPORTE FEDERAL' => $federal,
            'TRANSPORTE PRIVADO' => [],
        ];

        return self::$tipoServicioPorModalidadCache;
    }

    public function requiereModalidad(?string $tipoCarga): bool
    {
        if ($tipoCarga === null || $tipoCarga === '') {
            return false;
        }

        try {
            $row = ConnectionManager::get('default')->execute(
                'SELECT requiere_modalidad FROM catalogo_tipo_carga WHERE codigo = :c LIMIT 1',
                ['c' => $tipoCarga],
            )->fetch('assoc');
            if ($row) {
                return (int)$row['requiere_modalidad'] === 1;
            }
        } catch (\Throwable) {
            // fallback
        }

        return in_array($tipoCarga, ['carga_general', 'carga_especializada'], true);
    }

    public function esMaterialPeligroso(?string $tipoCarga): bool
    {
        if ($tipoCarga === null || $tipoCarga === '') {
            return false;
        }

        try {
            $row = ConnectionManager::get('default')->execute(
                'SELECT es_material_peligroso FROM catalogo_tipo_carga WHERE codigo = :c LIMIT 1',
                ['c' => $tipoCarga],
            )->fetch('assoc');
            if ($row) {
                return (int)$row['es_material_peligroso'] === 1;
            }
        } catch (\Throwable) {
            // fallback
        }

        return $tipoCarga === 'material_peligroso';
    }

    /** @return list<string> */
    public function codigosTipoCargaMp(): array
    {
        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT codigo FROM catalogo_tipo_carga WHERE activo = 1 AND es_material_peligroso = 1',
            )->fetchAll('assoc');
            if ($rows !== []) {
                return array_map(static fn(array $r): string => (string)$r['codigo'], $rows);
            }
        } catch (\Throwable) {
            // fallback
        }

        return ['material_peligroso'];
    }
}
