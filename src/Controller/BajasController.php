<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\BajasVehicularesTable;
use App\Model\Entity\BajaVehicular;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class BajasController extends AppController
{
    public function index(): void
    {
        $filtroVista = $this->datosFiltroEmpresaVista();

        $empresaIds = $this->empresaIdsAlcance();

        $query = $this->fetchTable('BajasVehiculares')
            ->find()
            ->contain(['Vehiculos', 'Empresas'])
            ->orderBy([
                'BajasVehiculares.empresa_id' => 'ASC',
                'BajasVehiculares.fecha_baja' => 'DESC',
                'BajasVehiculares.created_at' => 'DESC',
            ]);

        // Alcance estricto por empresas accesibles / filtro ?empresa_id=
        if ($empresaIds === []) {
            $query->where(['0 = 1']);
        } else {
            $query->where(['BajasVehiculares.empresa_id IN' => $empresaIds]);
        }

        $bajas = $query->all();

        $pasos          = BajasVehicularesTable::pasos();
        $bajasEnProceso = [];
        $historial      = [];

        foreach ($bajas as $baja) {
            // Defensa: no filtrar fuera del alcance aunque llegue un registro suelto.
            if (!in_array((int)$baja->empresa_id, $empresaIds, true)) {
                continue;
            }
            if ($baja->estatus_tramite !== 'completado') {
                $bajasEnProceso[] = $baja;
            } else {
                $historial[] = $baja;
            }
        }

        $this->set(compact('bajasEnProceso', 'historial', 'pasos') + $filtroVista);
    }

    public function add(): ?Response
    {
        $bajasTable = $this->fetchTable('BajasVehiculares');
        $baja = $bajasTable->newEmptyEntity();

        $vehiculos = $this->vehiculosParaSelect((int)$baja->vehiculo_id);

        $motivoOptions = [
            'siniestro' => 'Siniestro',
            'reemplazo' => 'Reemplazo',
            'venta' => 'Venta',
            'robo' => 'Robo',
            'otro' => 'Otro',
        ];

        $estatusOptions = [
            'iniciado' => 'Iniciado',
            'pendiente_placas' => 'Pendiente placas',
            'placas_devueltas' => 'Placas devueltas',
            'tramite_baja' => 'Trámite baja',
            'completado' => 'Completado',
        ];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['estatus_tramite'] = $data['estatus_tramite'] ?? 'iniciado';

            // Si no mandan empresa_id, intentar derivarlo del vehículo
            if (empty($data['empresa_id']) && !empty($data['vehiculo_id'])) {
                try {
                    $veh = $this->fetchTable('Vehiculos')->get((int)$data['vehiculo_id']);
                    $data['empresa_id'] = $veh->empresa_id;
                } catch (RecordNotFoundException) {
                    $this->Flash->error('El vehículo seleccionado no existe.');
                    $this->set(compact('baja', 'vehiculos', 'motivoOptions', 'estatusOptions'));
                    return null;
                }
            }
            if (!$this->vehiculoEnAlcance((int)($data['vehiculo_id'] ?? 0))
                || !$this->entidadEnAlcanceEmpresa((int)($data['empresa_id'] ?? 0))) {
                $this->Flash->error('No tienes acceso a ese vehículo/empresa.');
                $this->set(compact('baja', 'vehiculos', 'motivoOptions', 'estatusOptions'));

                return null;
            }

            $baja = $bajasTable->patchEntity($baja, $data);
            if ($bajasTable->save($baja)) {
                $this->inactivarVehiculoSiBajaCompletada($baja);
                $this->Flash->success('Trámite de baja creado.');

                return $this->redirect(['action' => 'edit', $baja->id]);
            }
            $this->Flash->error('No se pudo crear el trámite. Revisa los datos.');
        }

        $this->set(compact('baja', 'vehiculos', 'motivoOptions', 'estatusOptions'));
        return null;
    }

    public function avanzarPaso(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $bajasTable = $this->fetchTable('BajasVehiculares');
        try {
            $baja = $bajasTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Trámite no encontrado.');

            return $this->redirect(['action' => 'index']);
        }
        if (!$this->entidadEnAlcanceEmpresa((int)$baja->empresa_id)) {
            $this->Flash->error('No tienes acceso a este trámite.');

            return $this->redirect(['action' => 'index']);
        }

        $orden = array_column(BajasVehicularesTable::pasos(), 'key');
        $idx = array_search($baja->estatus_tramite, $orden, true);
        if ($idx === false || $idx >= count($orden) - 1) {
            $this->Flash->warning('El trámite ya está en el último paso.');

            return $this->redirect(['action' => 'edit', $id]);
        }

        $error = $this->validarPasoBaja($baja);
        if ($error !== null) {
            $this->Flash->error($error);

            return $this->redirect(['action' => 'edit', $id]);
        }

        $baja->estatus_tramite = $orden[$idx + 1];
        if ($bajasTable->save($baja)) {
            $this->inactivarVehiculoSiBajaCompletada($baja);
            $this->Flash->success('Trámite avanzado al siguiente paso.');
        } else {
            $this->Flash->error('No se pudo avanzar el trámite.');
        }

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function edit(int $id): ?Response
    {
        $bajasTable = $this->fetchTable('BajasVehiculares');
        try {
            $baja = $bajasTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Trámite no encontrado.');
            return $this->redirect(['action' => 'index']);
        }
        if (!$this->entidadEnAlcanceEmpresa((int)$baja->empresa_id)) {
            $this->Flash->error('No tienes acceso a este trámite.');

            return $this->redirect(['action' => 'index']);
        }

        $vehiculos = $this->vehiculosParaSelect((int)$baja->vehiculo_id);

        $motivoOptions = [
            'siniestro' => 'Siniestro',
            'reemplazo' => 'Reemplazo',
            'venta' => 'Venta',
            'robo' => 'Robo',
            'otro' => 'Otro',
        ];

        $estatusOptions = [
            'iniciado' => 'Iniciado',
            'pendiente_placas' => 'Pendiente placas',
            'placas_devueltas' => 'Placas devueltas',
            'tramite_baja' => 'Trámite baja',
            'completado' => 'Completado',
        ];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (empty($data['empresa_id']) && !empty($data['vehiculo_id'])) {
                try {
                    $veh = $this->fetchTable('Vehiculos')->get((int)$data['vehiculo_id']);
                    $data['empresa_id'] = $veh->empresa_id;
                } catch (RecordNotFoundException) {
                    $this->Flash->error('El vehículo seleccionado no existe.');
                    $this->set(compact('baja', 'vehiculos', 'motivoOptions', 'estatusOptions'));
                    return null;
                }
            }

            $baja = $bajasTable->patchEntity($baja, $data);
            if ($bajasTable->save($baja)) {
                $this->inactivarVehiculoSiBajaCompletada($baja);
                $this->Flash->success('Trámite actualizado.');

                return $this->redirect(['action' => 'edit', $baja->id]);
            }
            $this->Flash->error('No se pudo actualizar el trámite. Revisa los datos.');
        }

        $pasos = BajasVehicularesTable::pasos();

        $this->set(compact('baja', 'vehiculos', 'motivoOptions', 'estatusOptions', 'pasos'));
        return null;
    }

    private function validarPasoBaja(BajaVehicular $baja): ?string
    {
        return match ((string)$baja->estatus_tramite) {
            'iniciado' => (empty($baja->vehiculo_id) || empty($baja->motivo_baja))
                ? 'Completa vehículo y motivo antes de avanzar.'
                : null,
            'placas_devueltas' => empty($baja->placas_devueltas)
                ? 'Marca las placas como devueltas antes de avanzar.'
                : null,
            'tramite_baja' => empty($baja->numero_folio_baja)
                ? 'Captura el folio SICT antes de completar el trámite.'
                : null,
            default => null,
        };
    }

    /** @return array<int, string> */
    private function vehiculosParaSelect(int $vehiculoActualId = 0): array
    {
        $vehiculos = $this->vehiculosListAlcance(true);
        if ($vehiculoActualId > 0 && !isset($vehiculos[$vehiculoActualId]) && $this->vehiculoEnAlcance($vehiculoActualId)) {
            try {
                $veh = $this->fetchTable('Vehiculos')->get($vehiculoActualId);
                $vehiculos[$vehiculoActualId] = (string)$veh->placas;
            } catch (RecordNotFoundException) {
            }
        }

        return $vehiculos;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $bajasTable = $this->fetchTable('BajasVehiculares');
        try {
            $baja = $bajasTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Trámite no encontrado.');

            return $this->redirect(['action' => 'index']);
        }
        if (!$this->entidadEnAlcanceEmpresa((int)$baja->empresa_id)) {
            $this->Flash->error('No tienes acceso a este trámite.');

            return $this->redirect(['action' => 'index']);
        }

        if ($bajasTable->delete($baja)) {
            $this->Flash->success('Trámite eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar el trámite.');
        }

        return $this->redirect(['action' => 'index']);
    }

    private function inactivarVehiculoSiBajaCompletada(BajaVehicular $baja): void
    {
        if ($baja->estatus_tramite !== 'completado' || empty($baja->vehiculo_id)) {
            return;
        }
        $vehTable = $this->fetchTable('Vehiculos');
        try {
            $veh = $vehTable->get((int)$baja->vehiculo_id);
        } catch (RecordNotFoundException) {
            return;
        }
        if ($veh->estatus === 'inactivo') {
            return;
        }
        $veh->estatus = 'inactivo';
        $vehTable->save($veh);
    }
}
