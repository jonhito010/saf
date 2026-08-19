<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuditoriaService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Historial de verificaciones (nunca se elimina).
 */
class VerificacionesController extends AppController
{
    public function index(): void
    {
        $filtroVista = $this->datosFiltroEmpresaVista();
        $empresaIds = $this->empresaIdsAlcance();
        $q = trim((string)$this->request->getQuery('q', ''));
        $resultado = trim((string)$this->request->getQuery('resultado', ''));
        $fechaDesde = trim((string)$this->request->getQuery('fecha_desde', ''));
        $fechaHasta = trim((string)$this->request->getQuery('fecha_hasta', ''));

        $query = $this->fetchTable('VerificacionesHistorial')
            ->find()
            ->orderBy(['VerificacionesHistorial.fecha' => 'DESC', 'VerificacionesHistorial.id' => 'DESC']);

        if ($empresaIds === []) {
            $query->where(['0 = 1']);
        } else {
            $query->where(['VerificacionesHistorial.empresa_id IN' => $empresaIds]);
        }

        if ($resultado !== '') {
            $query->where(['VerificacionesHistorial.resultado' => $resultado]);
        }
        if ($fechaDesde !== '') {
            $query->where(['VerificacionesHistorial.fecha >=' => $fechaDesde . ' 00:00:00']);
        }
        if ($fechaHasta !== '') {
            $query->where(['VerificacionesHistorial.fecha <=' => $fechaHasta . ' 23:59:59']);
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'VerificacionesHistorial.tipo LIKE' => $like,
                    'VerificacionesHistorial.resultado LIKE' => $like,
                    'VerificacionesHistorial.detalle LIKE' => $like,
                ],
            ]);
        }

        $verificaciones = $this->paginate($query, ['limit' => 40, 'maxLimit' => 100]);
        $this->set(compact('verificaciones', 'q', 'resultado', 'fechaDesde', 'fechaHasta') + $filtroVista);
    }

    public function add(): ?Response
    {
        $empresas = $this->empresasAccesiblesSesion();
        if ($empresas === []) {
            $this->Flash->error('No hay empresa asociada a tu sesión.');

            return $this->redirect(['action' => 'index']);
        }

        $form = [
            'empresa_id' => $this->empresaIdSesion() ?? (int)array_key_first($empresas),
            'operador_id' => '',
            'vehiculo_id' => '',
            'tipo' => 'verificacion',
            'resultado' => 'aprobado',
            'detalle' => '',
            'fecha' => date('Y-m-d\TH:i'),
        ];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $form = array_merge($form, [
                'empresa_id' => (int)($data['empresa_id'] ?? 0),
                'operador_id' => (int)($data['operador_id'] ?? 0) ?: null,
                'vehiculo_id' => (int)($data['vehiculo_id'] ?? 0) ?: null,
                'tipo' => trim((string)($data['tipo'] ?? 'verificacion')),
                'resultado' => trim((string)($data['resultado'] ?? '')),
                'detalle' => trim((string)($data['detalle'] ?? '')),
                'fecha' => (string)($data['fecha'] ?? date('Y-m-d H:i:s')),
            ]);

            if (!$this->entidadEnAlcanceEmpresa((int)$form['empresa_id'])) {
                $this->Flash->error('No tienes acceso a esa empresa.');
            } elseif ($form['resultado'] === '') {
                $this->Flash->error('El resultado es obligatorio.');
            } else {
                $table = $this->fetchTable('VerificacionesHistorial');
                $entity = $table->newEntity([
                    'empresa_id' => (int)$form['empresa_id'],
                    'operador_id' => $form['operador_id'],
                    'vehiculo_id' => $form['vehiculo_id'],
                    'usuario_id' => $this->usuarioIdSesion(),
                    'tipo' => $form['tipo'] !== '' ? $form['tipo'] : 'verificacion',
                    'resultado' => $form['resultado'],
                    'detalle' => $form['detalle'] !== '' ? $form['detalle'] : null,
                    'fecha' => new DateTime($form['fecha']),
                ]);

                if ($table->save($entity)) {
                    (new AuditoriaService())->registrarLog(
                        'create',
                        'verificaciones',
                        'Verificación registrada #' . $entity->id,
                        ['resultado' => $form['resultado'], 'operador_id' => $form['operador_id']],
                        $this->usuarioIdSesion(),
                        (int)$form['empresa_id'],
                    );
                    $this->Flash->success('Verificación guardada en el historial.');

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error('No se pudo guardar la verificación.');
            }
        }

        $operadoresOpts = [];
        $vehiculosOpts = [];
        $eid = (int)$form['empresa_id'];
        if ($eid > 0 && $this->entidadEnAlcanceEmpresa($eid)) {
            foreach ($this->fetchTable('Operadores')->find()
                ->select(['id', 'nombre', 'apellido_paterno', 'apellido_materno'])
                ->where(['empresa_id' => $eid, 'estatus IN' => ['activo', 'inactivo', 'suspendido']])
                ->orderBy(['apellido_paterno' => 'ASC', 'nombre' => 'ASC'])
                ->all() as $op) {
                $operadoresOpts[(int)$op->id] = trim($op->nombre . ' ' . $op->apellido_paterno . ' ' . ($op->apellido_materno ?? ''));
            }
            foreach ($this->fetchTable('Vehiculos')->find()
                ->select(['id', 'placas', 'marca', 'modelo'])
                ->where(['empresa_id' => $eid])
                ->orderBy(['placas' => 'ASC'])
                ->all() as $v) {
                $vehiculosOpts[(int)$v->id] = trim(($v->placas ?? '') . ' · ' . ($v->marca ?? '') . ' ' . ($v->modelo ?? ''));
            }
        }

        $this->set(compact('form', 'empresas', 'operadoresOpts', 'vehiculosOpts'));

        return null;
    }

    /**
     * El historial nunca se elimina (requisito de negocio).
     */
    public function delete(int $id): Response
    {
        throw new ForbiddenException('El historial de verificaciones no se puede eliminar.');
    }
}
