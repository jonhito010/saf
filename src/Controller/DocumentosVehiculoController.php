<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\Http\Exception\NotFoundException;
use App\Model\Table\DocumentosVehiculoTable;
use App\Utility\DocumentoUtil;

class DocumentosVehiculoController extends AppController
{
    public function index(): Response
    {
        return $this->redirect(['controller' => 'Vehiculos', 'action' => 'index']);
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('DocumentosVehiculo');
        $documento = $table->newEmptyEntity();

        $vehiculos = $this->vehiculosListAlcance(true);

        $tiposDocumento = DocumentosVehiculoTable::tiposDocumento();

        $vehiculoFijo = false;
        $vehiculoIdQuery = $this->request->getQuery('vehiculo_id');
        $vehiculoId = null;
        if ($vehiculoIdQuery !== null && $vehiculoIdQuery !== '') {
            $vid = (int)$vehiculoIdQuery;
            if (!isset($vehiculos[$vid]) || !$this->vehiculoEnAlcance($vid)) {
                throw new NotFoundException('Vehículo no encontrado.');
            }
            $documento->vehiculo_id = $vid;
            $vehiculoId = $vid;
            $vehiculoFijo = true;
        } elseif (count($vehiculos) === 1) {
            $documento->vehiculo_id = (int)array_key_first($vehiculos);
            $vehiculoId = $documento->vehiculo_id;
            $vehiculoFijo = true;
        }

        $tipoPre = (string)$this->request->getQuery('tipo', '');
        if ($tipoPre !== '' && isset($tiposDocumento[$tipoPre])) {
            $documento->tipo_documento = $tipoPre;
        }

        $origenCodigo = $this->inferirOrigenVehiculo(
            $vehiculoId,
            (string)($documento->tipo_documento ?? $tipoPre),
        );

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            if ($vehiculoIdQuery !== null && $vehiculoIdQuery !== '') {
                $data['vehiculo_id'] = (int)$vehiculoIdQuery;
            } elseif (count($vehiculos) === 1) {
                $data['vehiculo_id'] = (int)array_key_first($vehiculos);
            }
            $vidPost = (int)($data['vehiculo_id'] ?? 0);
            if (!$this->vehiculoEnAlcance($vidPost)) {
                $this->Flash->error('No tienes acceso a ese vehículo.');
                $this->set(compact('documento', 'vehiculos', 'vehiculoFijo', 'tiposDocumento', 'origenCodigo'));
                $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Vehiculos', 'action' => 'index']);
                $this->set(compact('urlVolver'));

                return null;
            }

            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'vehiculo';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/vehiculo/',
                $allowed,
            );
            if (isset($result['error'])) {
                $this->Flash->error($result['error']);
                $omitSave = true;
            } elseif (isset($result['ruta'])) {
                $data['ruta_archivo'] = $result['ruta'];
                $data['nombre_archivo'] = $result['nombre'];
            }

            if (empty($data['estatus'])) {
                $data['estatus'] = 'vigente';
            }

            $documento = $table->patchEntity($documento, $data);
            if (!$omitSave) {
                if ($table->save($documento)) {
                    $this->Flash->success('Documento registrado.');
                    $origen = $this->inferirOrigenVehiculo(
                        (int)$documento->vehiculo_id,
                        (string)$documento->tipo_documento,
                    );

                    return $this->redirectTrasDocumento(
                        $origen,
                        ['controller' => 'Vehiculos', 'action' => 'index'],
                    );
                }
                $this->Flash->error('No se pudo registrar el documento.');
            }
        }

        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Vehiculos', 'action' => 'index']);
        $this->set(compact('documento', 'vehiculos', 'vehiculoFijo', 'tiposDocumento', 'origenCodigo', 'urlVolver'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('DocumentosVehiculo');
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirectTrasDocumento(
                null,
                ['controller' => 'Vehiculos', 'action' => 'index'],
            );
        }

        if (!$this->vehiculoEnAlcance((int)$documento->vehiculo_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Vehiculos', 'action' => 'index']);
        }

        $vehiculos = $this->vehiculosListAlcance(true);

        $tiposDocumento = DocumentosVehiculoTable::tiposDocumento();
        $origenCodigo = $this->inferirOrigenVehiculo(
            (int)$documento->vehiculo_id,
            (string)$documento->tipo_documento,
        );

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $vidPost = (int)($data['vehiculo_id'] ?? $documento->vehiculo_id);
            if (!$this->vehiculoEnAlcance($vidPost)) {
                $this->Flash->error('No tienes acceso a ese vehículo.');
                $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Vehiculos', 'action' => 'index']);
                $this->set(compact('documento', 'vehiculos', 'tiposDocumento', 'origenCodigo', 'urlVolver'));

                return null;
            }
            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'vehiculo';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/vehiculo/',
                $allowed,
                requerido: false,
                rutaExistente: (string)($documento->ruta_archivo ?? ''),
            );
            if (isset($result['error'])) {
                $this->Flash->error($result['error']);
                $omitSave = true;
            } elseif (isset($result['ruta'])) {
                DocumentoUtil::eliminarArchivoFisico((string)($documento->ruta_archivo ?? ''));
                $data['ruta_archivo'] = $result['ruta'];
                $data['nombre_archivo'] = $result['nombre'];
            }
            $documento = $table->patchEntity($documento, $data);
            if (!$omitSave && $table->save($documento)) {
                $this->Flash->success('Documento actualizado.');
                $origen = $this->inferirOrigenVehiculo(
                    (int)$documento->vehiculo_id,
                    (string)$documento->tipo_documento,
                );

                return $this->redirectTrasDocumento(
                    $origen,
                    ['controller' => 'Vehiculos', 'action' => 'index'],
                );
            }
            if (!$omitSave) {
                $this->Flash->error('No se pudo actualizar el documento.');
            }
        }

        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Vehiculos', 'action' => 'index']);
        $this->set(compact('documento', 'vehiculos', 'tiposDocumento', 'origenCodigo', 'urlVolver'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('DocumentosVehiculo');
        $origen = $this->codigoOrigenDocumento();
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirectTrasDocumento(
                $origen,
                ['controller' => 'Vehiculos', 'action' => 'index'],
            );
        }

        if (!$this->vehiculoEnAlcance((int)$documento->vehiculo_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Vehiculos', 'action' => 'index']);
        }

        if ($origen === null) {
            $origen = $this->inferirOrigenVehiculo(
                (int)$documento->vehiculo_id,
                (string)$documento->tipo_documento,
            );
        }

        if ($table->delete($documento)) {
            $this->Flash->success('Documento eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar el documento.');
        }

        return $this->redirectTrasDocumento(
            $origen,
            ['controller' => 'Vehiculos', 'action' => 'index'],
        );
    }
}
