<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosEmpresaTable;
use App\Utility\DocumentoUtil;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Documentos adicionales de empresa (materiales / residuos peligrosos).
 * Usa la tabla documentos_empresa con tipos del catálogo adicional.
 */
class DocumentosAdicionalesController extends AppController
{
    public function index(): void
    {
        $filtroVista = $this->datosFiltroEmpresaVista();
        $table = $this->fetchTable('DocumentosEmpresa');
        $tiposAdic = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());

        $documentos = [];
        $docStats = ['total' => 0, 'vigente' => 0, 'por_vencer' => 0, 'vencido' => 0];
        if ($tiposAdic !== []) {
            $query = $table->find()
                ->contain(['Empresas'])
                ->where(['DocumentosEmpresa.tipo_documento IN' => $tiposAdic])
                ->orderBy(['DocumentosEmpresa.updated_at' => 'DESC']);

            $this->aplicarAlcanceEmpresa($query, 'DocumentosEmpresa.empresa_id');

            $filtroQ = trim((string)$this->request->getQuery('q', ''));
            $filtroEstatus = (string)$this->request->getQuery('estatus', '');
            $filtroTipo = (string)$this->request->getQuery('tipo', '');

            if ($filtroQ !== '') {
                $term = '%' . $filtroQ . '%';
                $query->where([
                    'OR' => [
                        'DocumentosEmpresa.tipo_documento LIKE' => $term,
                        'DocumentosEmpresa.nombre_archivo LIKE' => $term,
                    ],
                ]);
            }
            if ($filtroEstatus !== '' && in_array($filtroEstatus, ['vigente', 'por_vencer', 'vencido'], true)) {
                $query->where(['DocumentosEmpresa.estatus' => $filtroEstatus]);
            }
            if ($filtroTipo !== '' && isset(DocumentosEmpresaTable::tiposDocumentoAdicionales()[$filtroTipo])) {
                $query->where(['DocumentosEmpresa.tipo_documento' => $filtroTipo]);
            }

            $documentos = $query->all();
            foreach ($documentos as $d) {
                $docStats['total']++;
                match ($d->estatus ?? '') {
                    'vencido' => $docStats['vencido']++,
                    'por_vencer' => $docStats['por_vencer']++,
                    default => $docStats['vigente']++,
                };
            }
        } else {
            $filtroQ = trim((string)$this->request->getQuery('q', ''));
            $filtroEstatus = (string)$this->request->getQuery('estatus', '');
            $filtroTipo = (string)$this->request->getQuery('tipo', '');
        }

        $this->set([
            'documentos' => $documentos,
            'docStats' => $docStats,
            'tiposDocumento' => DocumentosEmpresaTable::tiposDocumentoAdicionales(),
            'filtroQ' => $filtroQ,
            'filtroEstatus' => $filtroEstatus,
            'filtroTipo' => $filtroTipo,
        ] + $filtroVista);
    }

    public function add(): ?Response
    {
        $empresaId = $this->empresaIdSesion();
        if ($empresaId === null) {
            $this->Flash->error('No hay empresa asociada a tu sesión.');

            return $this->redirect(['action' => 'index']);
        }

        $table = $this->fetchTable('DocumentosEmpresa');
        $documento = $table->newEmptyEntity();
        $documento->empresa_id = $empresaId;

        $empresasTable = $this->fetchTable('Empresas');
        $nombreCol = $empresasTable->razonSocialField();
        $empresaNombre = (string)($empresasTable->get($empresaId, fields: ['id', $nombreCol])->get($nombreCol) ?? '');

        $tiposDocumento = DocumentosEmpresaTable::tiposDocumentoAdicionales();
        $tipoPre = (string)$this->request->getQuery('tipo', '');
        if ($tipoPre !== '' && isset($tiposDocumento[$tipoPre])) {
            $documento->tipo_documento = $tipoPre;
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['empresa_id'] = $empresaId;
            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'empresa' . DIRECTORY_SEPARATOR . 'adicionales';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/empresa/adicionales/',
                $allowed,
            );
            if (isset($result['error'])) {
                $this->Flash->error($result['error']);
                $omitSave = true;
            } elseif (isset($result['ruta'])) {
                $data['ruta_archivo'] = $result['ruta'];
                $data['nombre_archivo'] = $result['nombre'];
            }

            $tipoDoc = (string)($data['tipo_documento'] ?? '');
            if (!$this->fetchTable('TiposDocumento')->codigoExiste($tipoDoc, 'empresa', 'adicional')) {
                $this->Flash->error('Tipo de documento adicional no válido.');
                $omitSave = true;
            }

            if (empty($data['estatus'])) {
                $data['estatus'] = 'vigente';
            }

            $documento = $table->patchEntity($documento, $data);
            if (!$omitSave && $table->save($documento)) {
                $this->Flash->success('Documento adicional registrado.');

                return $this->redirectTrasDocumento(
                    $this->codigoOrigenDocumento(),
                    ['action' => 'index'],
                );
            }
            if (!$omitSave) {
                $this->Flash->error('No se pudo registrar el documento.');
            }
        }

        $origenCodigo = $this->codigoOrigenDocumento() ?? 'adicional';
        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['action' => 'index']);
        $this->set(compact('documento', 'empresaNombre', 'tiposDocumento', 'origenCodigo', 'urlVolver'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('DocumentosEmpresa');
        try {
            $documento = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Documento no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!DocumentosEmpresaTable::esTipoAdicional((string)$documento->tipo_documento)) {
            throw new NotFoundException('No es un documento adicional.');
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$documento->empresa_id)) {
            throw new NotFoundException('Documento no encontrado.');
        }

        $tiposDocumento = DocumentosEmpresaTable::tiposDocumentoAdicionales();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $omitSave = false;
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
            $uploadDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'empresa' . DIRECTORY_SEPARATOR . 'adicionales';
            $result = DocumentoUtil::procesarUpload(
                $this->request->getUploadedFile('archivo'),
                $uploadDir,
                '/uploads/empresa/adicionales/',
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
                    $this->codigoOrigenDocumento() ?? 'adicional',
                    ['action' => 'index'],
                );
            }
            $this->Flash->error('No se pudo actualizar.');
        }

        $origenCodigo = $this->codigoOrigenDocumento() ?? 'adicional';
        $urlVolver = $this->urlDesdeOrigen($origenCodigo, ['action' => 'index']);
        $this->set(compact('documento', 'tiposDocumento', 'origenCodigo', 'urlVolver'));
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
            return $this->redirect(['action' => 'index']);
        }

        if (!DocumentosEmpresaTable::esTipoAdicional((string)$documento->tipo_documento)) {
            throw new NotFoundException();
        }
        if (!$this->entidadEnAlcanceEmpresa((int)$documento->empresa_id)) {
            throw new NotFoundException();
        }

        if ($table->delete($documento)) {
            DocumentoUtil::eliminarArchivoFisico((string)($documento->ruta_archivo ?? ''));
            $this->Flash->success('Documento eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar.');
        }

        return $this->redirectTrasDocumento(
            $this->codigoOrigenDocumento() ?? 'adicional',
            ['action' => 'index'],
        );
    }
}
