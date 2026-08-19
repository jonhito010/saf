<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use App\Utility\EstadosSat;

class UbicacionesController extends AppController
{
    public function index(): void
    {
        $query = $this->fetchTable('Ubicaciones')
            ->find()
            ->orderBy(['Ubicaciones.created' => 'DESC', 'Ubicaciones.id' => 'DESC']);

        $ubicaciones = $this->paginate($query, [
            'limit' => 30,
            'maxLimit' => 200,
        ]);

        $this->set(compact('ubicaciones'));
    }

    public function add(): ?Response
    {
        $ubicacionesTable = $this->fetchTable('Ubicaciones');
        $ubicacion = $ubicacionesTable->newEmptyEntity();

        $tipoUbicacionOptions = [
            'Origen' => 'Origen',
            'Destino' => 'Destino',
        ];

        if ($this->request->is('post')) {
            $ubicacion = $ubicacionesTable->patchEntity($ubicacion, $this->request->getData());
            if ($ubicacionesTable->save($ubicacion)) {
                $this->Flash->success('Ubicación registrada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo registrar la ubicación. Revisa los datos.');
        }

        $this->set(compact('ubicacion', 'tipoUbicacionOptions'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $ubicacionesTable = $this->fetchTable('Ubicaciones');
        try {
            $ubicacion = $ubicacionesTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Ubicación no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        $tipoUbicacionOptions = [
            'Origen' => 'Origen',
            'Destino' => 'Destino',
        ];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $ubicacion = $ubicacionesTable->patchEntity($ubicacion, $this->request->getData());
            if ($ubicacionesTable->save($ubicacion)) {
                $this->Flash->success('Ubicación actualizada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo actualizar la ubicación. Revisa los datos.');
        }

        $this->set(compact('ubicacion', 'tipoUbicacionOptions'));
        return null;
    }

    // ── Autocomplete ubicaciones guardadas (por tipo) ───────────────────────

    public function autocomplete(): Response
    {
        $this->request->allowMethod(['get']);
        $q    = trim((string)$this->request->getQuery('q', ''));
        $tipo = trim((string)$this->request->getQuery('tipo', ''));

        $ub = $this->fetchTable('Ubicaciones');

        $conditions = [];
        if ($tipo !== '') {
            $conditions['tipo_ubicacion'] = $tipo;
        }
        if ($q !== '') {
            $conditions['OR'] = [
                'rfc LIKE'          =>'%' . $q . '%',
                'nombre LIKE'       => '%' . $q . '%',
                'id_ubicacion LIKE' => '%' . $q . '%',
            ];
        }

        $rows = $ub->find()
            ->select([
                'id', 'tipo_ubicacion', 'id_ubicacion', 'rfc', 'nombre',
                'calle', 'numero_exterior', 'numero_interior',
                'colonia', 'municipio', 'estado', 'codigo_postal',
            ])
            ->where($conditions)
            ->orderBy(['nombre' => 'ASC', 'rfc' => 'ASC'])
            ->limit(20)
            ->all()
            ->toArray();

        $out = array_map(static fn($r) => [
            'id'              => $r->id,
            'tipo'            => $r->tipo_ubicacion,
            'id_ubicacion'    => $r->id_ubicacion ?? '',
            'rfc'             => $r->rfc ?? '',
            'nombre'          => $r->nombre ?? '',
            'label'           => ($r->rfc ?? '') . ' — ' . ($r->nombre ?? ''),
            'calle'           => $r->calle ?? '',
            'numero_exterior' => $r->numero_exterior ?? '',
            'numero_interior' => $r->numero_interior ?? '',
            'colonia'         => $r->colonia ?? '',
            'municipio'       => $r->municipio ?? '',
            'estado'          => $r->estado ?? '',
            'codigo_postal'   => $r->codigo_postal ?? '',
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    // ── Autocomplete (catálogos BD) ──────────────────────────────────────────

    public function autocompleteEstados(): Response
    {
        $this->request->allowMethod(['get']);
        $qRaw = trim((string)$this->request->getQuery('q', ''));
        $qNorm = self::_normText($qRaw);

        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "estado IS NOT NULL AND estado <> ''";
        if ($qNorm !== '') {
            // Primero intentamos por prefijo clave (ej. "19", "01")
            $where .= " AND estado LIKE ?";
            $params[] = $qRaw . '%';
        }

        $rows = $cn->execute(
            "SELECT DISTINCT estado
             FROM municipios
             WHERE {$where}
             ORDER BY estado
             LIMIT 80",
            $params
        )->fetchAll('assoc');

        $seen = [];
        $out = [];

        foreach ($rows as $r) {
            $key = trim((string)($r['estado'] ?? ''));
            if ($key === '') {
                continue;
            }

            $clave2 = EstadosSat::normalizeKey($key);
            $nombre = EstadosSat::catalog()[$clave2] ?? '';

            // Si hay texto de búsqueda, filtra también por nombre normalizado
            if ($qNorm !== '') {
                $claveNorm = self::_normText($clave2);
                $nombreNorm = self::_normText($nombre);

                $match =
                    str_starts_with($claveNorm, $qNorm)
                    || ($nombreNorm !== '' && str_contains($nombreNorm, $qNorm));

                if (!$match) {
                    continue;
                }
            }

            $seen[$clave2] = true;

            $label = $nombre !== '' ? $nombre : $clave2;
            $out[] = [
                'value' => $clave2,
                'label' => $label,
                'key' => $clave2,
                'name' => $nombre,
            ];
        }

        // Complementar con catálogo SAT si el usuario escribe nombre y no apareció en DISTINCT (por datos incompletos)
        if ($qNorm !== '') {
            foreach (EstadosSat::catalog() as $clave2 => $nombre) {
                $clave2 = EstadosSat::normalizeKey($clave2);
                if (isset($seen[$clave2])) {
                    continue;
                }

                $claveNorm = self::_normText($clave2);
                $nombreNorm = self::_normText($nombre);

                $match =
                    str_starts_with($claveNorm, $qNorm)
                    || ($nombreNorm !== '' && str_contains($nombreNorm, $qNorm));

                if (!$match) {
                    continue;
                }

                $seen[$clave2] = true;
                $out[] = [
                    'value' => $clave2,
                    'label' => $nombre,
                    'key' => $clave2,
                    'name' => $nombre,
                ];
            }
        }

        usort($out, static fn($a, $b) => strcmp((string)$a['value'], (string)$b['value']));
        $out = array_slice($out, 0, 50);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    private static function _normText(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
    }

    public function autocompleteMunicipios(): Response
    {
        $this->request->allowMethod(['get']);
        $estado = strtoupper(trim((string)$this->request->getQuery('estado', '')));
        $q = trim((string)$this->request->getQuery('q', ''));

        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "municipio IS NOT NULL AND municipio <> ''";

        if ($estado !== '') {
            $where .= " AND estado = ?";
            $params[] = $estado;
        }
        if ($q !== '') {
            $where .= " AND (municipio LIKE ? OR descripcion LIKE ?)";
            $params[] = $q . '%';
            $params[] = '%' . $q . '%';
        }

        $rows = $cn->execute(
            "SELECT municipio, descripcion
             FROM municipios
             WHERE {$where}
             ORDER BY municipio
             LIMIT 50",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'value' => (string)($r['municipio'] ?? ''),
            'label' => (string)($r['descripcion'] ?? ''),
            'key' => (string)($r['municipio'] ?? ''),
            'name' => (string)($r['descripcion'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    public function autocompleteColonias(): Response
    {
        $this->request->allowMethod(['get']);
        $cp = trim((string)$this->request->getQuery('cp', ''));
        $q = trim((string)$this->request->getQuery('q', ''));

        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "colonia IS NOT NULL AND colonia <> ''";

        if ($cp !== '') {
            $where .= " AND codigopostal = ?";
            $params[] = $cp;
        }
        if ($q !== '') {
            $where .= " AND (colonia LIKE ? OR nombre LIKE ?)";
            $params[] = $q . '%';
            $params[] = '%' . $q . '%';
        }

        $rows = $cn->execute(
            "SELECT colonia, nombre
             FROM cpcolonias
             WHERE {$where}
             ORDER BY colonia
             LIMIT 80",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'value' => (string)($r['colonia'] ?? ''),
            'label' => (string)($r['nombre'] ?? ''),
            'key' => (string)($r['colonia'] ?? ''),
            'name' => (string)($r['nombre'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    public function autocompleteLocalidades(): Response
    {
        $this->request->allowMethod(['get']);
        $estado = strtoupper(trim((string)$this->request->getQuery('estado', '')));
        $q = trim((string)$this->request->getQuery('q', ''));

        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "localidad IS NOT NULL AND localidad <> ''";

        if ($estado !== '') {
            $where .= " AND estado = ?";
            $params[] = $estado;
        }
        if ($q !== '') {
            $where .= " AND (localidad LIKE ? OR descripcion LIKE ?)";
            $params[] = $q . '%';
            $params[] = '%' . $q . '%';
        }

        $rows = $cn->execute(
            "SELECT localidad, descripcion
             FROM cplocalidad
             WHERE {$where}
             ORDER BY localidad
             LIMIT 50",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'value' => (string)($r['localidad'] ?? ''),
            'label' => (string)($r['descripcion'] ?? ''),
            'key' => (string)($r['localidad'] ?? ''),
            'name' => (string)($r['descripcion'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    public function autocompleteCodigoPostal(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q', ''));

        $cn = ConnectionManager::get('default');
        $params = [];
        $where = "codigopostal IS NOT NULL AND codigopostal <> ''";

        if ($q !== '') {
            // Permite buscar por inicio o por cualquier parte
            $where .= " AND codigopostal LIKE ?";
            $params[] = $q . '%';
        }

        $rows = $cn->execute(
            "SELECT DISTINCT codigopostal
             FROM cpcolonias
             WHERE {$where}
             ORDER BY codigopostal
             LIMIT 50",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'value' => (string)($r['codigopostal'] ?? ''),
            'label' => (string)($r['codigopostal'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }
}

