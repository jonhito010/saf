<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\TiposDocumentoTable;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * Catálogo fijo de tipos de documento (solo lectura + importación CSV).
 */
class TiposDocumentoController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $rol = $this->rolIdSesion();
        if ($rol !== 1 && $rol !== 2) {
            throw new ForbiddenException('No tienes permiso para ver el catálogo de tipos de documento.');
        }
    }

    public function index(): void
    {
        $table = $this->fetchTable('TiposDocumento');
        $filas = [];
        try {
            $filas = $table->find()
                ->orderBy(['ambito' => 'ASC', 'orden' => 'ASC', 'nombre' => 'ASC'])
                ->all()
                ->toList();
        } catch (\Throwable) {
            $filas = [];
        }

        $porAmbito = [
            'empresa' => ['label' => 'Empresa', 'items' => []],
            'vehiculo' => ['label' => 'Vehículo', 'items' => []],
            'operador' => ['label' => 'Operador', 'items' => []],
        ];
        foreach ($filas as $row) {
            $amb = (string)$row->ambito;
            if (isset($porAmbito[$amb])) {
                $porAmbito[$amb]['items'][] = $row;
            }
        }

        $puedeImportar = in_array($this->rolIdSesion(), [1, 2], true);

        $this->set(compact('porAmbito', 'puedeImportar'));
    }

    public function importar(): ?Response
    {
        $this->request->allowMethod(['post']);

        if (!in_array($this->rolIdSesion(), [1, 2], true)) {
            throw new ForbiddenException('Solo superadmin o consultor pueden importar el catálogo.');
        }

        $upload = $this->request->getUploadedFile('archivo_csv');
        if (!$upload || $upload->getError() !== UPLOAD_ERR_OK) {
            $this->Flash->error('Selecciona un archivo CSV válido.');
            return $this->redirect(['action' => 'index']);
        }

        $tmp = $upload->getStream()->getMetadata('uri');
        if (!is_string($tmp) || !is_readable($tmp)) {
            $this->Flash->error('No se pudo leer el archivo CSV.');
            return $this->redirect(['action' => 'index']);
        }

        $handle = fopen($tmp, 'rb');
        if ($handle === false) {
            $this->Flash->error('No se pudo abrir el archivo CSV.');
            return $this->redirect(['action' => 'index']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->Flash->error('El CSV está vacío.');
            return $this->redirect(['action' => 'index']);
        }

        $header = array_map(static fn($h) => mb_strtolower(trim((string)$h)), $header);
        $requeridos = ['codigo', 'nombre', 'ambito', 'categoria', 'orden'];
        foreach ($requeridos as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                $this->Flash->error('El CSV debe incluir las columnas: codigo, nombre, ambito, categoria, orden.');
                return $this->redirect(['action' => 'index']);
            }
        }

        $idx = array_flip($header);
        $conn = ConnectionManager::get('default');
        $now = date('Y-m-d H:i:s');
        $insertados = 0;
        $actualizados = 0;
        $errores = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }
            $codigo = mb_strtolower(trim((string)($row[$idx['codigo']] ?? '')));
            $nombre = trim((string)($row[$idx['nombre']] ?? ''));
            $ambito = mb_strtolower(trim((string)($row[$idx['ambito']] ?? '')));
            $categoria = trim((string)($row[$idx['categoria']] ?? 'general'));
            $orden = (int)($row[$idx['orden']] ?? 0);

            if ($codigo === '' || $nombre === '' || !in_array($ambito, ['empresa', 'vehiculo', 'operador'], true)) {
                $errores++;
                continue;
            }

            $existente = $conn->execute(
                'SELECT id FROM tipos_documento WHERE ambito = :ambito AND codigo = :codigo LIMIT 1',
                ['ambito' => $ambito, 'codigo' => $codigo],
            )->fetch('assoc');

            if (!empty($existente)) {
                $conn->execute(
                    'UPDATE tipos_documento
                     SET nombre = :nombre, categoria = :categoria, orden = :orden,
                         activo = 1, editable = 0, updated_at = :updated_at
                     WHERE id = :id',
                    [
                        'nombre' => $nombre,
                        'categoria' => $categoria !== '' ? $categoria : 'general',
                        'orden' => $orden,
                        'updated_at' => $now,
                        'id' => (int)$existente['id'],
                    ],
                );
                $actualizados++;
            } else {
                $conn->insert('tipos_documento', [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'ambito' => $ambito,
                    'categoria' => $categoria !== '' ? $categoria : 'general',
                    'orden' => $orden,
                    'activo' => 1,
                    'editable' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertados++;
            }
        }
        fclose($handle);

        TiposDocumentoTable::limpiarCache();

        if ($insertados === 0 && $actualizados === 0 && $errores > 0) {
            $this->Flash->error('No se importó ningún registro. Revisa el formato del CSV.');
        } else {
            $msg = "Importación completada: {$insertados} nuevo(s), {$actualizados} actualizado(s).";
            if ($errores > 0) {
                $msg .= " {$errores} fila(s) omitida(s).";
            }
            $this->Flash->success($msg);
        }

        return $this->redirect(['action' => 'index']);
    }
}
