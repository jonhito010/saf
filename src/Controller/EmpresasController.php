<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CumplimientoService;
use App\Service\UsuarioEmpresaService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class EmpresasController extends AppController
{
    public function index(): ?Response
    {
        $empresasTable = $this->fetchTable('Empresas');
        $docsTable = $this->fetchTable('DocumentosEmpresa');
        $usuarioEmpresa = new UsuarioEmpresaService();
        $usuarioId = $this->usuarioIdSesion();
        $filtroVista = $this->datosFiltroEmpresaVista();
        $crearNueva = $this->request->getQuery('nueva') === '1';
        $gestionarId = (int)$this->request->getQuery('gestionar', 0);
        $q = trim((string)$this->request->getQuery('q', ''));
        $empresaIdActiva = $this->empresaIdSesion();
        $empresasAccesibles = $this->empresasAccesiblesSesion();
        $nombreCol = $empresasTable->razonSocialField();

        $modoFormulario = false;
        $empresa = $empresasTable->newEmptyEntity();
        $documentos = [];
        $sinDocumentosCorporativos = true;
        $empresasDocumentacion = [];

        if ($this->request->is('post')) {
            $empresaIdPost = (int)$this->request->getData('id', 0);
            $esAlta = $empresaIdPost <= 0;

            if ($esAlta) {
                $entity = $empresasTable->newEmptyEntity();
            } else {
                if (!$this->usuarioTieneAccesoEmpresa($empresaIdPost)) {
                    $this->Flash->error('No tienes acceso a esa empresa.');
                    return $this->redirect(['action' => 'index']);
                }
                try {
                    $entity = $empresasTable->get($empresaIdPost);
                } catch (RecordNotFoundException) {
                    $this->Flash->error('Empresa no encontrada.');
                    return $this->redirect(['action' => 'index']);
                }
            }

            $data = $this->request->getData();
            $data['estatus'] = $data['estatus'] ?? 'activa';
            $data['transporte_materiales_peligrosos'] = !empty($data['transporte_materiales_peligrosos']) ? 1 : 0;
            if ($usuarioId !== null) {
                if ($esAlta) {
                    $data['created_by'] = $usuarioId;
                }
                $data['updated_by'] = $usuarioId;
            }

            foreach (['pem_key', 'pem_cer'] as $field) {
                $up = $this->request->getUploadedFile($field);
                if (!$up || $up->getError() !== UPLOAD_ERR_OK) {
                    unset($data[$field]);
                }
            }

            $entity = $empresasTable->patchEntity($entity, $data);
            if ($empresasTable->save($entity)) {
                if ($esAlta && $usuarioId !== null) {
                    $usuarioEmpresa->vincular($usuarioId, (int)$entity->id, true);
                    $this->establecerEmpresaActiva((int)$entity->id);
                } elseif (!$esAlta) {
                    $this->establecerEmpresaActiva((int)$entity->id);
                }

                $this->Flash->success($esAlta ? 'Empresa registrada y vinculada a tu cuenta.' : 'Empresa actualizada.');
                return $this->redirect([
                    'action' => 'index',
                    '?' => ['gestionar' => (int)$entity->id],
                ]);
            }
            $errors = $entity->getErrors();
            if (!empty($errors)) {
                $msgs = [];
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $msg) {
                        $msgs[] = "{$field}: {$msg}";
                    }
                }
                $this->Flash->error('No se pudo guardar la empresa: ' . implode(' | ', $msgs));
            } else {
                $this->Flash->error('No se pudo guardar la empresa. Revisa los datos.');
            }
            $empresa = $entity;
            $modoFormulario = true;
        } elseif ($crearNueva) {
            $modoFormulario = true;
            $empresa = $empresasTable->newEmptyEntity();
        } elseif ($gestionarId > 0) {
            if (!$this->usuarioTieneAccesoEmpresa($gestionarId)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
                return $this->redirect(['action' => 'index']);
            }
            try {
                $empresa = $empresasTable->get($gestionarId);
                $modoFormulario = true;
                $this->establecerEmpresaActiva($gestionarId);
            } catch (RecordNotFoundException) {
                $this->Flash->error('Empresa no encontrada.');
                return $this->redirect(['action' => 'index']);
            }
        }

        if ($modoFormulario) {
            $empresaId = (int)($empresa->id ?? 0);
            $documentos = $empresaId > 0
                ? $docsTable->find()
                    ->where(['empresa_id' => $empresaId])
                    ->orderBy(['tipo_documento' => 'ASC'])
                    ->all()
                : [];
            $sinDocumentosCorporativos = $empresaId === 0
                || $docsTable->find()->where(['empresa_id' => $empresaId])->count() === 0;

            $cumplimientoDetalle = $empresaId > 0
                ? (new CumplimientoService())->resumenEmpresa($empresaId)
                : null;

            $this->set(compact(
                'empresa',
                'documentos',
                'sinDocumentosCorporativos',
                'empresasAccesibles',
                'empresaIdActiva',
                'modoFormulario',
                'empresasDocumentacion',
                'cumplimientoDetalle',
            ) + $filtroVista);

            return null;
        }

        $ids = $this->empresaIdsAlcance();
        if ($ids !== []) {
            $query = $empresasTable->find()
                ->where(['Empresas.id IN' => $ids]);

            if ($q !== '') {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $or = [
                    'Empresas.' . $nombreCol . ' LIKE' => $like,
                    'Empresas.rfc LIKE' => $like,
                    'Empresas.email LIKE' => $like,
                    'Empresas.telefono LIKE' => $like,
                ];
                if ($nombreCol !== 'nombre' && $empresasTable->getSchema()->hasColumn('nombre')) {
                    $or['Empresas.nombre LIKE'] = $like;
                }
                if ($empresasTable->getSchema()->hasColumn('razon_social') && $nombreCol !== 'razon_social') {
                    $or['Empresas.razon_social LIKE'] = $like;
                }
                $query->where(['OR' => $or]);
            }

            $empresas = $query
                ->orderBy(['Empresas.' . $nombreCol => 'ASC'])
                ->all();

            foreach ($empresas as $emp) {
                $eid = (int)$emp->id;
                $docs = $docsTable->find()
                    ->where(['empresa_id' => $eid])
                    ->orderBy(['tipo_documento' => 'ASC'])
                    ->all();

                $docTotal = $docVigente = $docPorVencer = $docVencido = 0;
                foreach ($docs as $doc) {
                    $docTotal++;
                    match ($doc->estatus ?? '') {
                        'vencido' => $docVencido++,
                        'por_vencer' => $docPorVencer++,
                        default => $docVigente++,
                    };
                }
                $cumplResumen = (new CumplimientoService())->resumenEmpresa($eid);
                $cumplimientoPct = $cumplResumen['porcentaje'];

                $permisoSictTxt = '—';
                if (!empty($emp->vencimiento_permiso_sict)) {
                    $vSict = $emp->vencimiento_permiso_sict;
                    $permisoSictTxt = $vSict instanceof \DateTimeInterface
                        ? $vSict->format('d/m/Y')
                        : (string)$vSict;
                }

                $empresasDocumentacion[] = [
                    'empresa' => $emp,
                    'documentos' => $docs,
                    'docTotal' => $docTotal,
                    'docVigente' => $docVigente,
                    'docPorVencer' => $docPorVencer,
                    'docVencido' => $docVencido,
                    'cumplimientoPct' => $cumplimientoPct,
                    'cumplimientoOk' => $cumplResumen['ok'],
                    'cumplimientoRequeridos' => $cumplResumen['total'],
                    'cumplimientoFaltantes' => $cumplResumen['faltantes'],
                    'mpActivo' => $cumplResumen['mp_activo'],
                    'permisoSictTxt' => $permisoSictTxt,
                    'sinDocumentos' => $docTotal === 0,
                ];
            }
        }

        $this->set(compact(
            'empresa',
            'documentos',
            'sinDocumentosCorporativos',
            'empresasAccesibles',
            'empresaIdActiva',
            'modoFormulario',
            'empresasDocumentacion',
            'q',
        ) + $filtroVista);

        return null;
    }

    public function seleccionar(): Response
    {
        $this->request->allowMethod(['post']);

        $id = (int)$this->request->getData('empresa_id', 0);
        if ($id <= 0) {
            $this->Flash->error('Empresa inválida.');
            return $this->redirect($this->referer(['action' => 'index'], true));
        }

        if (!$this->usuarioTieneAccesoEmpresa($id)) {
            $this->Flash->error('No tienes acceso a esa empresa.');
            return $this->redirect($this->referer(['action' => 'index'], true));
        }

        $this->establecerEmpresaActiva($id);
        $this->Flash->success('Empresa activa actualizada.');

        return $this->redirect($this->referer(['controller' => 'Dashboard', 'action' => 'index'], true));
    }

    // ── SUBIR CSD ────────────────────────────────────────────────────────────
    public function subir(): Response
    {
        $this->request->allowMethod(['post']);

        $empresasTable = $this->fetchTable('Empresas');
        $empresaId = $this->empresaIdSesion();
        if ($empresaId === null) {
            $this->Flash->error('Registra la empresa antes de cargar certificados.');
            return $this->redirect(['action' => 'index']);
        }
        try {
            $empresa = $empresasTable->get($empresaId);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Empresa no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        $contrasena = (string)$this->request->getData('contrasena');
        $uploadCer  = $this->request->getUploadedFile('certificado');
        $uploadKey  = $this->request->getUploadedFile('archivo_key');

        if (!$uploadCer || $uploadCer->getError() !== UPLOAD_ERR_OK ||
            !$uploadKey  || $uploadKey->getError()  !== UPLOAD_ERR_OK) {
            $this->Flash->error('Debes subir ambos archivos (.cer y .key).');
            return $this->redirect(['action' => 'index']);
        }

        // Directorio de certificados (fuera de webroot no es necesario, pero lo dejamos ahí
        // porque el sistema pancho también los guardaba en un path accesible)
        $certDir = WWW_ROOT . 'certificados';
        if (!is_dir($certDir)) {
            mkdir($certDir, 0775, true);
        }

        $rfc     = preg_replace('/[^A-Z0-9]/i', '', (string)$empresa->rfc);
        $tmpCer  = $certDir . DS . 'tmp_' . $rfc . '.cer';
        $tmpKey  = $certDir . DS . 'tmp_' . $rfc . '.key';

        $uploadCer->moveTo($tmpCer);
        $uploadKey->moveTo($tmpKey);

        // ── 1. Convertir DER → PEM ────────────────────────────────────────────
        // Detección por byte inicial: DER siempre empieza con 0x30 (ASN.1 SEQUENCE).
        // No usar str_contains porque el binario DER puede contener '-----BEGIN' por azar.
        $cerRaw = (string)file_get_contents($tmpCer);
        if (strlen($cerRaw) === 0) {
            @unlink($tmpCer); @unlink($tmpKey);
            $this->Flash->error('El archivo .cer está vacío o no se pudo leer.');
            return $this->redirect(['action' => 'index']);
        }
        $cerPemStr = (ord($cerRaw[0]) === 0x30)
            ? $this->_derCertToPem($cerRaw)
            : ltrim(str_replace(["\r\n", "\r"], "\n", $cerRaw), "\xEF\xBB\xBF");

        $keyRaw = (string)file_get_contents($tmpKey);
        if (strlen($keyRaw) === 0) {
            @unlink($tmpCer); @unlink($tmpKey);
            $this->Flash->error('El archivo .key está vacío o no se pudo leer.');
            return $this->redirect(['action' => 'index']);
        }
        $keyEncPem = (ord($keyRaw[0]) === 0x30)
            ? ("-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
               . chunk_split(base64_encode($keyRaw), 64, "\n")
               . "-----END ENCRYPTED PRIVATE KEY-----\n")
            : ltrim(str_replace(["\r\n", "\r"], "\n", $keyRaw), "\xEF\xBB\xBF");

        @unlink($tmpCer);
        @unlink($tmpKey);

        // Limpiar buffer de errores OpenSSL antes de comenzar
        while (openssl_error_string() !== false);

        // ── 2. Validar certificado ────────────────────────────────────────────
        $certRes = @openssl_x509_read($cerPemStr);
        if ($certRes === false) {
            $err = openssl_error_string() ?: 'formato inválido';
            $this->Flash->error("El archivo .cer no es un certificado x509 válido: {$err}");
            return $this->redirect(['action' => 'index']);
        }

        // ── 3. Descifrar llave privada con contraseña ─────────────────────────
        while (openssl_error_string() !== false);
        $keyRes = @openssl_pkey_get_private($keyEncPem, $contrasena);
        if ($keyRes === false) {
            $err = openssl_error_string() ?: 'contraseña incorrecta o archivo .key inválido';
            $this->Flash->error("No se pudo abrir la llave privada: {$err}");
            return $this->redirect(['action' => 'index']);
        }

        // ── 4. Verificar que el certificado corresponde a la llave ────────────
        if (!@openssl_x509_check_private_key($certRes, $keyRes)) {
            $this->Flash->error('El certificado .cer no corresponde a la llave privada .key.');
            return $this->redirect(['action' => 'index']);
        }

        // ── 5. Parsear información del certificado ────────────────────────────
        $infoCert = @openssl_x509_parse($certRes);
        if ($infoCert === false) {
            $this->Flash->error('No se pudo leer la información del certificado.');
            return $this->redirect(['action' => 'index']);
        }

        // Validar que sea CSD (Sello Digital)
        $usoKey = $infoCert['extensions']['keyUsage'] ?? '';
        if (stripos($usoKey, 'Digital Signature') === false) {
            $this->Flash->error('El certificado no es de tipo CSD (Certificado de Sello Digital).');
            return $this->redirect(['action' => 'index']);
        }

        // Validar que el RFC del certificado corresponde a la empresa
        $x500  = $infoCert['subject']['x500UniqueIdentifier'] ?? '';
        $rfcCert = strtoupper(trim(explode('/', $x500)[0]));
        if ($rfcCert !== '' && strtoupper($rfc) !== $rfcCert) {
            $this->Flash->error("El certificado no corresponde al RFC del emisor (cert: {$rfcCert}).");
            return $this->redirect(['action' => 'index']);
        }

        // ── 6. Extraer número de serie (formato SAT: caracteres en posiciones impares del hex) ──
        $serialHex = $infoCert['serialNumberHex']
            ?? strtoupper(dechex((int)($infoCert['serialNumber'] ?? 0)));
        $noSerie = '';
        for ($z = 1; $z < strlen($serialHex); $z += 2) {
            $noSerie .= $serialHex[$z];
        }

        $fechaInicio = date('Y-m-d', (int)$infoCert['validFrom_time_t']);
        $fechaFin    = date('Y-m-d', (int)$infoCert['validTo_time_t']);

        // ── 7. Exportar llave descifrada y guardar archivos PEM ───────────────
        @openssl_pkey_export($keyRes, $keyPemDecrypted);

        $cerPemPath = $certDir . DS . $rfc . '.cer.pem';
        $keyPemPath = $certDir . DS . $rfc . '.key.pem';

        file_put_contents($cerPemPath, $cerPemStr);
        file_put_contents($keyPemPath, $keyPemDecrypted);

        // ── 8. Actualizar empresa ─────────────────────────────────────────────
        $empresa->pem_cer            = 'certificados/' . $rfc . '.cer.pem';
        $empresa->pem_key            = 'certificados/' . $rfc . '.key.pem';
        $empresa->numero_certificado = $noSerie;
        $empresa->fecha_inicio_cert  = $fechaInicio;
        $empresa->fecha_fin_cert     = $fechaFin;

        if ($empresasTable->save($empresa)) {
            $this->Flash->success("CSD cargado correctamente — No. Serie: {$noSerie}");
        } else {
            $this->Flash->error('Los archivos se procesaron pero no se pudieron guardar los datos.');
        }

        return $this->redirect(['action' => 'index']);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private function _derCertToPem(string $der): string
    {
        return "-----BEGIN CERTIFICATE-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END CERTIFICATE-----\n";
    }

    // ── REDIRECTS ────────────────────────────────────────────────────────────

    public function add(): Response
    {
        return $this->redirect(['action' => 'index', '?' => ['nueva' => '1']]);
    }

    public function edit(int $id): Response
    {
        return $this->redirect(['action' => 'index']);
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!$this->usuarioTieneAccesoEmpresa($id)) {
            $this->Flash->error('No tienes acceso a esa empresa.');

            return $this->redirect(['action' => 'index']);
        }

        $empresasTable = $this->fetchTable('Empresas');
        try {
            $empresa = $empresasTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Empresa no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        $empresa->estatus = 'baja';
        if ($empresasTable->save($empresa)) {
            $this->Flash->success('Empresa dada de baja.');
        } else {
            $this->Flash->error('No se pudo dar de baja la empresa.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
