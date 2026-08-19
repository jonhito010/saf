<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosOperadorTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class DocumentosOperadorController extends AppController
{
    public function index(): Response
    {
        return $this->redirect(['controller' => 'Operadores', 'action' => 'index']);
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('DocumentosOperador');
        $documento = $table->newEmptyEntity();

        $operadores = $this->operadoresListAlcance(true);

        $operadorId = (int)$this->request->getQuery('operador_id', 0);
        if ($operadorId > 0 && isset($operadores[$operadorId])) {
            $documento->operador_id = $operadorId;
        }

        $origenCodigo = $operadorId > 0
            ? $this->codigoOrigenOperador($operadorId)
            : $this->codigoOrigenDocumento();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $oid = (int)($data['operador_id'] ?? 0);
            if (!$this->operadorEnAlcance($oid)) {
                $this->Flash->error('No tienes acceso a ese operador.');
                $tiposDocumento = DocumentosOperadorTable::tiposDocumento();
                $this->set(compact('documento', 'operadores', 'origenCodigo', 'tiposDocumento'));

                return null;
            }
            $documento = $table->patchEntity($documento, $data);
            if ($table->save($documento)) {
                $this->Flash->success('Documento registrado.');
                $origen = $this->codigoOrigenOperador((int)$documento->operador_id);

                return $this->redirectTrasDocumento(
                    $origen,
                    ['controller' => 'Operadores', 'action' => 'index'],
                );
            }
            $this->Flash->error('No se pudo registrar el documento.');
        }

        $tiposDocumento = DocumentosOperadorTable::tiposDocumento();
        $this->set(compact('documento', 'operadores', 'origenCodigo', 'tiposDocumento'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('DocumentosOperador');
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirectTrasDocumento(
                null,
                ['controller' => 'Operadores', 'action' => 'index'],
            );
        }

        if (!$this->operadorEnAlcance((int)$documento->operador_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Operadores', 'action' => 'index']);
        }

        $operadores = $this->operadoresListAlcance(true);

        $origenCodigo = $this->codigoOrigenOperador((int)$documento->operador_id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $oid = (int)($data['operador_id'] ?? $documento->operador_id);
            if (!$this->operadorEnAlcance($oid)) {
                $this->Flash->error('No tienes acceso a ese operador.');
                $tiposDocumento = DocumentosOperadorTable::tiposDocumento();
                $this->set(compact('documento', 'operadores', 'origenCodigo', 'tiposDocumento'));

                return null;
            }
            $documento = $table->patchEntity($documento, $data);
            if ($table->save($documento)) {
                $this->Flash->success('Documento actualizado.');

                return $this->redirectTrasDocumento(
                    $this->codigoOrigenOperador((int)$documento->operador_id),
                    ['controller' => 'Operadores', 'action' => 'index'],
                );
            }
            $this->Flash->error('No se pudo actualizar el documento.');
        }

        $tiposDocumento = DocumentosOperadorTable::tiposDocumento();
        $this->set(compact('documento', 'operadores', 'origenCodigo', 'tiposDocumento'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('DocumentosOperador');
        $origen = $this->codigoOrigenDocumento();
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirectTrasDocumento(
                $origen,
                ['controller' => 'Operadores', 'action' => 'index'],
            );
        }

        if (!$this->operadorEnAlcance((int)$documento->operador_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Operadores', 'action' => 'index']);
        }

        if ($origen === null) {
            $origen = $this->codigoOrigenOperador((int)$documento->operador_id);
        }

        if ($table->delete($documento)) {
            $this->Flash->success('Documento eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar el documento.');
        }

        return $this->redirectTrasDocumento(
            $origen,
            ['controller' => 'Operadores', 'action' => 'index'],
        );
    }
}
