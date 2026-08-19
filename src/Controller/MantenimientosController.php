<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class MantenimientosController extends AppController
{
    public function index(): void
    {
        $ids = $this->empresaIdsAlcance();
        $query = $this->fetchTable('Mantenimientos')
            ->find()
            ->contain(['Vehiculos'])
            ->orderBy(['Mantenimientos.fecha' => 'DESC']);
        if ($ids === []) {
            $query->where(['0 = 1']);
        } else {
            $query->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $ids]));
        }

        $this->set([
            'mantenimientos' => $query->all(),
        ] + $this->datosFiltroEmpresaVista());
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('Mantenimientos');
        $mantenimiento = $table->newEmptyEntity();
        $vehiculos = $this->vehiculosListAlcance(true);

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if (!$this->vehiculoEnAlcance((int)($data['vehiculo_id'] ?? 0))) {
                $this->Flash->error('No tienes acceso a ese vehículo.');
                $this->set(compact('mantenimiento', 'vehiculos'));

                return null;
            }
            $mantenimiento = $table->patchEntity($mantenimiento, $data);
            if ($table->save($mantenimiento)) {
                $this->Flash->success('Mantenimiento registrado.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo registrar el mantenimiento.');
        }

        $this->set(compact('mantenimiento', 'vehiculos'));
        return null;
    }

    public function addForVehiculo(int $vehiculoId): ?Response
    {
        if (!$this->vehiculoEnAlcance($vehiculoId)) {
            $this->Flash->error('No tienes acceso a ese vehículo.');

            return $this->redirect(['controller' => 'Vehiculos', 'action' => 'index']);
        }

        $table = $this->fetchTable('Mantenimientos');
        $mantenimiento = $table->newEmptyEntity();
        $vehiculos = $this->vehiculosListAlcance(true);
        $mantenimiento->vehiculo_id = $vehiculoId;

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['vehiculo_id'] = $vehiculoId;
            $mantenimiento = $table->patchEntity($mantenimiento, $data);
            if ($table->save($mantenimiento)) {
                $this->Flash->success('Mantenimiento registrado.');
                return $this->redirect(['controller' => 'Vehiculos', 'action' => 'view', $vehiculoId]);
            }
            $this->Flash->error('No se pudo registrar el mantenimiento.');
        }

        $this->set(compact('mantenimiento', 'vehiculos', 'vehiculoId'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('Mantenimientos');
        try {
            $mantenimiento = $table->get($id, contain: ['Vehiculos']);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Mantenimiento no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $empresaId = (int)($mantenimiento->vehiculo->empresa_id ?? 0);
        if (!$this->entidadEnAlcanceEmpresa($empresaId) && !$this->vehiculoEnAlcance((int)$mantenimiento->vehiculo_id)) {
            $this->Flash->error('No tienes acceso a este mantenimiento.');

            return $this->redirect(['action' => 'index']);
        }

        $vehiculos = $this->vehiculosListAlcance(true);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (!$this->vehiculoEnAlcance((int)($data['vehiculo_id'] ?? $mantenimiento->vehiculo_id))) {
                $this->Flash->error('No tienes acceso a ese vehículo.');
                $this->set(compact('mantenimiento', 'vehiculos'));

                return null;
            }
            $mantenimiento = $table->patchEntity($mantenimiento, $data);
            if ($table->save($mantenimiento)) {
                $this->Flash->success('Mantenimiento actualizado.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo actualizar el mantenimiento.');
        }

        $this->set(compact('mantenimiento', 'vehiculos'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('Mantenimientos');
        try {
            $mantenimiento = $table->get($id, contain: ['Vehiculos']);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Mantenimiento no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->vehiculoEnAlcance((int)$mantenimiento->vehiculo_id)) {
            $this->Flash->error('No tienes acceso a este mantenimiento.');

            return $this->redirect(['action' => 'index']);
        }

        if ($table->delete($mantenimiento)) {
            $this->Flash->success('Mantenimiento eliminado.');
        } else {
            $this->Flash->error('No se pudo eliminar el mantenimiento.');
        }

        return $this->redirect(['action' => 'index']);
    }
}
