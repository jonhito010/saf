<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use App\Model\Table\DocumentosEmpresaTable;
use App\Utility\DocumentoUtil;

class DocumentosEmpresaController extends AppController
{
    public function index(): Response
    {
        return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('DocumentosEmpresa');
        $documento = $table->newEmptyEntity();

        $empresas = $this->empresasListAlcance();

        $tiposDocumento = DocumentosEmpresaTable::tiposDocumentoCorporativos();

        $returnToEmpresa = $this->request->getQuery('return') === 'empresa'
            || $this->codigoOrigenDocumento() === 'empresa';
        $empresaFija     = false;
        $origenCodigo    = 'empresa';

        $empresaIdQuery = $this->request->getQuery('empresa_id');
        if ($empresaIdQuery !== null && $empresaIdQuery !== '') {
            $eid = (int)$empresaIdQuery;
            if (!isset($empresas[$eid]) || !$this->entidadEnAlcanceEmpresa($eid)) {
                throw new NotFoundException('Empresa no encontrada.');
            }
            $documento->empresa_id = $eid;
            $empresaFija = true;
        } elseif (count($empresas) === 1) {
            $documento->empresa_id = (int)array_key_first($empresas);
            $empresaFija = true;
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if (($data['return_to'] ?? '') === 'empresa') {
                $returnToEmpresa = true;
            }
            if ($empresaIdQuery !== null && $empresaIdQuery !== '') {
                $data['empresa_id'] = (int)$empresaIdQuery;
            } elseif (count($empresas) === 1) {
                $data['empresa_id'] = (int)array_key_first($empresas);
            }
            $eidPost = (int)($data['empresa_id'] ?? 0);
            if (!$this->entidadEnAlcanceEmpresa($eidPost)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
                $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Empresas', 'action' => 'index']);
                $this->set(compact('documento', 'empresas', 'empresaFija', 'tiposDocumento', 'returnToEmpresa', 'origenCodigo', 'urlVolver'));

                return null;
            }

            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'empresa';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/empresa/',
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

                    return $this->redirectTrasDocumento(
                        $returnToEmpresa ? 'empresa' : null,
                        ['controller' => 'Empresas', 'action' => 'index'],
                    );
                }
                $this->Flash->error('No se pudo registrar el documento.');
            }
        }

        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Empresas', 'action' => 'index']);
        $this->set(compact('documento', 'empresas', 'empresaFija', 'tiposDocumento', 'returnToEmpresa', 'origenCodigo', 'urlVolver'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('DocumentosEmpresa');
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$documento->empresa_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        $empresas = $this->empresasListAlcance();

        $tiposDocumento = DocumentosEmpresaTable::tiposDocumentoCorporativos();

        $returnToEmpresa = $this->request->getQuery('return') === 'empresa'
            || $this->codigoOrigenDocumento() === 'empresa';
        $origenCodigo = 'empresa';

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $eidPost = (int)($data['empresa_id'] ?? $documento->empresa_id);
            if (!$this->entidadEnAlcanceEmpresa($eidPost)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
                $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Empresas', 'action' => 'index']);
                $this->set(compact('documento', 'empresas', 'tiposDocumento', 'returnToEmpresa', 'origenCodigo', 'urlVolver'));

                return null;
            }
            if (($data['return_to'] ?? '') === 'empresa') {
                $returnToEmpresa = true;
            }
            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'empresa';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/empresa/',
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

                return $this->redirectTrasDocumento(
                    $returnToEmpresa ? 'empresa' : null,
                    ['controller' => 'Empresas', 'action' => 'index'],
                );
            }
            if (!$omitSave) {
                $this->Flash->error('No se pudo actualizar el documento.');
            }
        }

        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['controller' => 'Empresas', 'action' => 'index']);
        $this->set(compact('documento', 'empresas', 'tiposDocumento', 'returnToEmpresa', 'origenCodigo', 'urlVolver'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('DocumentosEmpresa');
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');

            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$documento->empresa_id)) {
            $this->Flash->error('No tienes acceso a este documento.');

            return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
        }

        if ($table->delete($documento)) {
            DocumentoUtil::eliminarArchivoFisico((string)($documento->ruta_archivo ?? ''));
            $this->Flash->success('Documento eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar el documento.');
        }

        return $this->redirectTrasDocumento('empresa', ['controller' => 'Empresas', 'action' => 'index']);
    }
}

