<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;
use App\Utility\EstadosSat;
use App\Utility\EstadosSatCartaPorte;

class UbicacionesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ubicaciones');
        $this->setPrimaryKey('id');
        $this->setEntityClass('App\Model\Entity\Ubicacion');

        // La tabla tiene created/modified -> comportamiento clásico
        $this->addBehavior('Timestamp');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $tipo = (string)($data['tipo_ubicacion'] ?? '');
        $prefix = $tipo === 'Origen' ? 'OR' : ($tipo === 'Destino' ? 'DE' : '');

        // Generar/normalizar id_ubicacion: OR/DE + 6 dígitos (siempre acorde al tipo)
        if ($prefix !== '') {
            $raw = isset($data['id_ubicacion']) ? trim((string)$data['id_ubicacion']) : '';
            $digits = '';

            if ($raw === '') {
                // Buscar el mayor consecutivo usado para ese prefijo
                $row = $this->find()
                    ->select(['id_ubicacion'])
                    ->where(['id_ubicacion LIKE' => $prefix . '%'])
                    ->orderBy(['id_ubicacion' => 'DESC'])
                    ->first();

                $next = 1;
                if ($row && !empty($row->id_ubicacion)) {
                    $num = (int)preg_replace('/\D+/', '', (string)$row->id_ubicacion);
                    $next = $num > 0 ? ($num + 1) : 1;
                }
                $digits = (string)$next;
            } elseif (preg_match('/^(OR|DE)\s*(\d{1,6})$/i', $raw, $m)) {
                // Viene con prefijo + dígitos (posible prefijo incorrecto). Respetamos solo dígitos.
                $digits = $m[2];
            } elseif (preg_match('/^\d{1,6}$/', $raw)) {
                // Viene solo el número
                $digits = $raw;
            } else {
                // Intentar rescatar dígitos
                $digits = (string)(int)preg_replace('/\D+/', '', $raw);
            }

            $digits = substr(str_pad($digits === '' ? '1' : $digits, 6, '0', STR_PAD_LEFT), -6);
            $data['id_ubicacion'] = $prefix . $digits;
        }

        // Normalizar RFC a mayúsculas
        if (isset($data['rfc'])) {
            $data['rfc'] = strtoupper(trim((string)$data['rfc']));
        }

        // Autocomplete: permitir capturar "CLAVE — NOMBRE" o solo nombre.
        $this->normalizeEstadoSat($data);
        $this->normalizeCatalogField($data, 'municipio', 'municipio', 'municipios', 'estado');
        $this->normalizeCatalogField($data, 'localidad', 'localidad', 'cplocalidad', 'estado');
        // `ubicaciones.codigo_postal` filtra contra `cpcolonias.codigopostal`
        $this->normalizeCatalogField($data, 'colonia', 'colonia', 'cpcolonias', 'codigopostal', 'codigo_postal');
    }

    private function normalizeEstadoSat(ArrayObject $data): void
    {
        if (!isset($data['estado'])) {
            return;
        }

        $raw = trim((string)$data['estado']);
        if ($raw === '') {
            return;
        }

        $up = mb_strtoupper($raw, 'UTF-8');

        // Compat: claves de 3 letras (Carta Porte) → clave numérica SAT (01..32)
        if (preg_match('/^([A-Z]{3})\s*[—-]\s*/u', $up, $m)) {
            $letter = $m[1];
            $map = EstadosSatCartaPorte::letterToKey();
            if (isset($map[$letter])) {
                $data['estado'] = $map[$letter];
                return;
            }
        }

        if (preg_match('/^[A-Z]{3}$/', $up)) {
            $map = EstadosSatCartaPorte::letterToKey();
            if (isset($map[$up])) {
                $data['estado'] = $map[$up];
                return;
            }
        }

        // "19 — Nuevo León" / "01 — Aguascalientes"
        if (preg_match('/^(\d{1,2})\s*[—-]\s*/u', $raw, $m)) {
            $data['estado'] = EstadosSat::normalizeKey($m[1]);
            return;
        }

        // Clave numérica pura
        if (preg_match('/^\d{1,2}$/', $raw)) {
            $data['estado'] = EstadosSat::normalizeKey($raw);
            return;
        }

        // Resolver por nombre oficial del catálogo SAT (sin acentos)
        $needle = self::_normTextEs($raw);

        $exact = null;
        $partial = [];
        foreach (EstadosSat::catalog() as $clave => $nombre) {
            $clave2 = EstadosSat::normalizeKey($clave);
            $nombreNorm = self::_normTextEs($nombre);

            if ($nombreNorm === $needle) {
                $exact = $clave2;
                break;
            }
            if ($needle !== '' && str_contains($nombreNorm, $needle)) {
                $partial[$clave2] = true;
            }
        }

        if ($exact !== null) {
            $data['estado'] = $exact;
            return;
        }

        // Solo auto-resolver si hay coincidencia única por substring
        if (count($partial) === 1) {
            $data['estado'] = array_key_first($partial);
        }
    }

    private static function _normTextEs(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
    }

    private function normalizeCatalogField(
        ArrayObject $data,
        string $field,
        string $keyColumn,
        ?string $table,
        ?string $filterColumn = null,
        ?string $dataFieldKey = null
    ): void {
        if (!isset($data[$field])) {
            return;
        }

        $raw = trim((string)$data[$field]);
        if ($raw === '') {
            return;
        }

        // Si viene "CLAVE — ..." tomamos la clave
        if (preg_match('/^([A-Z0-9]+)\s*—/u', $raw, $m)) {
            $data[$field] = $m[1];
            return;
        }

        // Si no hay tabla, no podemos resolver por nombre
        if ($table === null) {
            return;
        }

        // Si parece que ya es clave (solo alfanumérico corto), lo dejamos.
        if (preg_match('/^[A-Z0-9]{1,10}$/i', $raw)) {
            return;
        }

        // Intentar resolver por nombre/descripción.
        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "{$keyColumn} IS NOT NULL AND {$keyColumn} <> ''";

        if ($filterColumn !== null && $filterColumn !== '') {
            $fk = $dataFieldKey ?: $filterColumn;
            if (isset($data[$fk]) && trim((string)$data[$fk]) !== '') {
                $where .= " AND {$filterColumn} = ?";
                $params[] = trim((string)$data[$fk]);
            }
        }

        // Cada catálogo tiene distinta columna de texto
        $textCol = $table === 'cpcolonias' ? 'nombre' : 'descripcion';
        if ($cn->execute("SHOW COLUMNS FROM `{$table}` LIKE '{$textCol}'")->fetch('assoc')) {
            $where .= " AND {$textCol} LIKE ?";
            $params[] = '%' . $raw . '%';
        } else {
            return;
        }

        $row = $cn->execute(
            "SELECT {$keyColumn}
             FROM {$table}
             WHERE {$where}
             ORDER BY {$keyColumn}
             LIMIT 1",
            $params
        )->fetch('assoc');

        if ($row && isset($row[$keyColumn]) && trim((string)$row[$keyColumn]) !== '') {
            $data[$field] = (string)$row[$keyColumn];
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('tipo_ubicacion', 'El tipo de ubicación es obligatorio.')
            ->inList('tipo_ubicacion', ['Origen', 'Destino'], 'Tipo de ubicación inválido.')

            ->notEmptyString('id_ubicacion', 'El id_ubicacion es obligatorio.')
            ->maxLength('id_ubicacion', 10, 'Máximo 10 caracteres.')
            ->regex('id_ubicacion', '/^(OR|DE)[0-9]{6}$/', 'Formato inválido. Debe ser OR/DE + 6 dígitos (ej. OR000001).')

            ->notEmptyString('rfc', 'El RFC es obligatorio.')
            ->maxLength('rfc', 13, 'Máximo 13 caracteres.')
            ->regex('rfc', '/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/i', 'RFC inválido.')

            ->notEmptyString('nombre', 'El nombre es obligatorio.')
            ->maxLength('nombre', 255, 'Máximo 255 caracteres.')

            ->notEmptyString('calle', 'La calle es obligatoria.')
            ->maxLength('calle', 255)
            ->notEmptyString('numero_exterior', 'El número exterior es obligatorio.')
            ->maxLength('numero_exterior', 50)
            ->allowEmptyString('numero_interior')
            ->maxLength('numero_interior', 50)

            ->notEmptyString('colonia', 'La colonia es obligatoria.')
            ->maxLength('colonia', 10)
            ->notEmptyString('municipio', 'El municipio es obligatorio.')
            ->maxLength('municipio', 5)
            ->notEmptyString('estado', 'El estado es obligatorio.')
            ->maxLength('estado', 3)
            ->notEmptyString('codigo_postal', 'El código postal es obligatorio.')
            ->maxLength('codigo_postal', 5)
            ->regex('codigo_postal', '/^[0-9]{5}$/', 'El C.P. debe tener 5 dígitos.')
            ->allowEmptyString('localidad')
            ->maxLength('localidad', 10)

            ->allowEmptyString('pais')
            ->maxLength('pais', 3, 'Máximo 3 caracteres (clave ISO).')

            ->allowEmptyString('referencia')
            ->maxLength('referencia', 255);

        return $validator;
    }
}

