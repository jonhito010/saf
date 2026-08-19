<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Operador;
use App\Service\AuditoriaService;
use App\Service\OperadorArchivosService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class OperadoresController extends AppController
{
    /** @var list<string> */
    private const ESTATUS_VALIDOS = ['activo', 'inactivo', 'suspendido', 'baja'];

    public function index(): void
    {
        $filtroVista = $this->datosFiltroEmpresaVista();
        $estatus = (string)$this->request->getQuery('estatus', 'activo');
        $q = trim((string)$this->request->getQuery('q', ''));

        $query = $this->fetchTable('Operadores')
            ->find()
            ->contain(['DocumentosOperador', 'Empresas'])
            ->orderBy([
                'Operadores.empresa_id' => 'ASC',
                'Operadores.apellido_paterno' => 'ASC',
                'Operadores.nombre' => 'ASC',
            ]);

        $this->aplicarAlcanceEmpresa($query, 'Operadores.empresa_id');

        if (in_array($estatus, self::ESTATUS_VALIDOS, true)) {
            $query->where(['Operadores.estatus' => $estatus]);
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'Operadores.nombre LIKE' => $like,
                    'Operadores.apellido_paterno LIKE' => $like,
                    'Operadores.apellido_materno LIKE' => $like,
                    "CONCAT(Operadores.nombre,' ',Operadores.apellido_paterno,' ',Operadores.apellido_materno) LIKE" => $like,
                    'Operadores.curp LIKE' => $like,
                    'Operadores.rfc LIKE' => $like,
                    'Operadores.num_licencia LIKE' => $like,
                    'Operadores.telefono LIKE' => $like,
                    'Operadores.email LIKE' => $like,
                ],
            ]);
        }

        $operadores = $this->paginate($query, [
            'limit' => 30,
            'maxLimit' => 100,
        ]);

        $tiposReq = \App\Model\Table\DocumentosOperadorTable::tiposRequeridos();

        $operadoresData = [];
        foreach ($operadores as $op) {
            $docsPorTipo = [];
            foreach ($op->documentos_operador as $doc) {
                $docsPorTipo[$doc->tipo_documento] = $doc;
            }

            $worst   = 'vigente';
            $faltantes = 0;
            foreach ($tiposReq as $tipo) {
                if (!isset($docsPorTipo[$tipo])) {
                    $faltantes++;
                    $worst = 'faltante';
                } elseif ($docsPorTipo[$tipo]->estatus === 'vencido' && $worst !== 'faltante') {
                    $worst = 'vencido';
                } elseif ($docsPorTipo[$tipo]->estatus === 'por_vencer' && !in_array($worst, ['vencido', 'faltante'], true)) {
                    $worst = 'por_vencer';
                }
            }

            $operadoresData[] = [
                'operador'   => $op,
                'docsPorTipo'=> $docsPorTipo,
                'estatus'    => $worst,
                'faltantes'  => $faltantes,
            ];
        }

        $this->set(compact('operadores', 'operadoresData', 'tiposReq', 'estatus', 'q') + $filtroVista);
    }

    public function add(): ?Response
    {
        $empresas = $this->empresasAccesiblesSesion();
        if ($empresas === []) {
            $this->Flash->error('No hay empresa asociada a tu sesión.');
            return $this->redirect(['action' => 'index']);
        }

        $empresaId = $this->empresaIdSesion() ?? (int)array_key_first($empresas);
        if (!$this->entidadEnAlcanceEmpresa($empresaId)) {
            $empresaId = (int)array_key_first($empresas);
        }

        $operadoresTable = $this->fetchTable('Operadores');
        $operador = $operadoresTable->newEmptyEntity();
        $operador->empresa_id = $empresaId;
        $empresaNombre = (string)($empresas[$empresaId] ?? '');
        $empresaFija = count($empresas) === 1;

        if ($this->request->is('post')) {
            $data = $this->filtrarDatosOperador($this->request->getData());
            $data['estatus'] = $data['estatus'] ?? 'activo';
            $empresaPost = (int)($data['empresa_id'] ?? $empresaId);
            if (!$this->entidadEnAlcanceEmpresa($empresaPost)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
                $this->set(compact('operador', 'empresaNombre', 'empresas', 'empresaFija') + ['docsPorTipo' => []]);

                return null;
            }
            $data['empresa_id'] = $empresaPost;

            $archivosService = new OperadorArchivosService();
            $erroresDocs = $archivosService->validarObligatorios($this->request);
            $operador = $operadoresTable->patchEntity($operador, $data);
            if ($erroresDocs !== []) {
                foreach ($erroresDocs as $msg) {
                    $this->Flash->error($msg);
                }
            } elseif ($operadoresTable->save($operador)) {
                $erroresArch = $archivosService->procesarDesdeRequest($operador, $this->request);
                if ($erroresArch !== []) {
                    foreach ($erroresArch as $msg) {
                        $this->Flash->warning($msg);
                    }
                }
                $this->registrarAuditoriaOperador('create', $operador);
                $this->Flash->success('Operador registrado correctamente.');
                return $this->redirect(['action' => 'edit', $operador->id]);
            } else {
                $this->flashErroresEntidad($operador, 'No se pudo registrar el operador.');
            }
        }

        $this->set(compact('operador', 'empresaNombre', 'empresas', 'empresaFija') + ['docsPorTipo' => []]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $operadoresTable = $this->fetchTable('Operadores');
        try {
            $operador = $operadoresTable->get($id, contain: ['DocumentosOperador']);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$operador->empresa_id)) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $empresaId = (int)$operador->empresa_id;
        $empresasTable = $this->fetchTable('Empresas');
        $nombreCol = $empresasTable->razonSocialField();
        $empresaNombre = (string)($empresasTable->get($empresaId, fields: ['id', $nombreCol])->get($nombreCol) ?? '');

        $docsPorTipo = [];
        foreach ($operador->documentos_operador as $doc) {
            $docsPorTipo[$doc->tipo_documento] = $doc;
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->filtrarDatosOperador($this->request->getData());
            $data['empresa_id'] = $empresaId;
            $camposAuditar = [
                'nombre', 'apellido_paterno', 'apellido_materno', 'num_licencia',
                'curp', 'rfc', 'telefono', 'email', 'estatus',
            ];
            $antes = [];
            foreach ($camposAuditar as $campo) {
                $antes[$campo] = $operador->get($campo);
            }
            $archivosService = new OperadorArchivosService();
            $erroresDocs = $archivosService->validarObligatorios($this->request, $docsPorTipo);
            $operador = $operadoresTable->patchEntity($operador, $data);
            if ($erroresDocs !== []) {
                foreach ($erroresDocs as $msg) {
                    $this->Flash->error($msg);
                }
            } elseif ($operadoresTable->save($operador)) {
                $despues = [];
                foreach ($camposAuditar as $campo) {
                    $despues[$campo] = $operador->get($campo);
                }
                (new AuditoriaService())->registrarCambiosEntidad(
                    'operador',
                    (int)$operador->id,
                    $antes,
                    $despues,
                    $camposAuditar,
                    $this->usuarioIdSesion(),
                    $empresaId,
                );
                $erroresArch = $archivosService->procesarDesdeRequest($operador, $this->request);
                if ($erroresArch !== []) {
                    foreach ($erroresArch as $msg) {
                        $this->Flash->warning($msg);
                    }
                } else {
                    $this->registrarAuditoriaOperador('update', $operador);
                    $this->Flash->success('Operador actualizado correctamente.');
                }
                return $this->redirect(['action' => 'edit', $operador->id]);
            } else {
                $this->flashErroresEntidad($operador, 'No se pudo actualizar el operador.');
                $operador = $operadoresTable->get($id, contain: ['DocumentosOperador']);
                $docsPorTipo = [];
                foreach ($operador->documentos_operador as $doc) {
                    $docsPorTipo[$doc->tipo_documento] = $doc;
                }
            }
        }

        $this->set(compact('operador', 'empresaNombre', 'docsPorTipo'));

        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $operadoresTable = $this->fetchTable('Operadores');
        try {
            $operador = $operadoresTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$operador->empresa_id)) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $motivo = trim((string)$this->request->getData('baja_motivo', ''));
        $schema = $operadoresTable->getSchema();
        $tieneBajaLogica = $schema->hasColumn('baja_at');

        if ($tieneBajaLogica) {
            $operador->estatus = 'baja';
            $operador->baja_at = date('Y-m-d H:i:s');
            $operador->baja_motivo = $motivo !== '' ? $motivo : null;
            if ($schema->hasColumn('baja_usuario_id')) {
                $operador->baja_usuario_id = $this->usuarioIdSesion();
            }
            $mensajeOk = 'Operador dado de baja correctamente.';
        } else {
            $operador->estatus = 'inactivo';
            $mensajeOk = 'Operador inactivado (baja lógica no disponible en BD).';
        }

        if ($operadoresTable->save($operador, ['validate' => false])) {
            $this->registrarAuditoriaOperador('delete', $operador, [
                'estatus' => (string)$operador->estatus,
                'baja_motivo' => $motivo,
            ]);
            $this->Flash->success($mensajeOk);
        } else {
            $this->flashErroresEntidad($operador, 'No se pudo dar de baja el operador.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function cambiarEstatus(int $id): Response
    {
        $this->request->allowMethod(['post']);

        $nuevoEstatus = (string)$this->request->getData('estatus', '');
        if (!in_array($nuevoEstatus, self::ESTATUS_VALIDOS, true)) {
            $this->Flash->error('Estatus inválido. Use activo, inactivo, suspendido o baja.');
            return $this->redirect($this->referer(['action' => 'index'], true));
        }

        $operadoresTable = $this->fetchTable('Operadores');
        try {
            $operador = $operadoresTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$operador->empresa_id)) {
            $this->Flash->error('Operador no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $estatusAnterior = (string)($operador->estatus ?? '');
        $operador->estatus = $nuevoEstatus;

        $schema = $operadoresTable->getSchema();
        if ($nuevoEstatus === 'baja' && $schema->hasColumn('baja_at')) {
            $operador->baja_at = date('Y-m-d H:i:s');
            $motivo = trim((string)$this->request->getData('baja_motivo', ''));
            if ($schema->hasColumn('baja_motivo')) {
                $operador->baja_motivo = $motivo !== '' ? $motivo : null;
            }
            if ($schema->hasColumn('baja_usuario_id')) {
                $operador->baja_usuario_id = $this->usuarioIdSesion();
            }
        }

        if ($operadoresTable->save($operador, ['validate' => false])) {
            $this->registrarAuditoriaOperador('status_change', $operador, [
                'estatus_anterior' => $estatusAnterior,
                'estatus_nuevo' => $nuevoEstatus,
            ]);
            $this->Flash->success('Estatus del operador actualizado.');
        } else {
            $this->flashErroresEntidad($operador, 'No se pudo cambiar el estatus del operador.');
        }

        return $this->redirect($this->referer(['action' => 'edit', $id], true));
    }

    /** @param array<string, mixed> $data */
    private function filtrarDatosOperador(array $data): array
    {
        unset(
            $data['foto'],
            $data['archivo_ine'],
            $data['archivo_licencia'],
            $data['archivo_comprobante_domicilio'],
            $data['fecha_vencimiento_ine'],
            $data['fecha_vencimiento_licencia'],
            $data['fecha_vencimiento_capacitacion'],
            $data['fecha_vencimiento_comprobante_domicilio'],
        );

        return $data;
    }

    private function flashErroresEntidad(Operador $operador, string $prefijo): void
    {
        $errores = $operador->getErrors();
        if ($errores === []) {
            $this->Flash->error($prefijo . ' Revisa los datos capturados.');
            return;
        }

        $labels = [
            'empresa_id' => 'Empresa',
            'nombre' => 'Nombre',
            'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno',
            'num_licencia' => 'Número de licencia',
            'estatus' => 'Estatus',
            'curp' => 'CURP',
            'rfc' => 'RFC',
            'telefono' => 'Teléfono',
            'email' => 'Correo electrónico',
        ];

        foreach ($errores as $campo => $msgs) {
            $label = $labels[$campo] ?? (string)$campo;
            foreach ((array)$msgs as $msg) {
                $this->Flash->error($prefijo . ' ' . $label . ': ' . (string)$msg);
            }
        }
    }

    /** @param array<string, mixed> $extra */
    private function registrarAuditoriaOperador(string $accion, Operador $operador, array $extra = []): void
    {
        if (!class_exists(AuditoriaService::class)) {
            return;
        }

        $nombre = trim((string)$operador->nombre_completo);
        if ($nombre === '') {
            $nombre = 'Operador #' . (int)$operador->id;
        }

        $descripcion = match ($accion) {
            'create' => 'Operador creado: ' . $nombre,
            'update' => 'Operador editado: ' . $nombre,
            'delete' => 'Operador dado de baja: ' . $nombre,
            'status_change' => 'Cambio de estatus de operador: ' . $nombre,
            default => 'Operador: ' . $nombre,
        };

        (new AuditoriaService())->registrarLog(
            $accion,
            'operadores',
            $descripcion,
            array_merge(['operador_id' => (int)$operador->id], $extra),
            $this->usuarioIdSesion(),
            (int)($operador->empresa_id ?? 0) ?: null,
        );
    }
}
