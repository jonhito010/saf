<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * @property \App\Model\Table\ClientesTable $Clientes
 */
class ClientesController extends AppController
{
    public function index()
    {
        $query = $this->Clientes->find()
            ->where(['Clientes.eliminado' => 0])
            ->orderByAsc('Clientes.nombre');
        if ($this->Clientes->getSchema()->hasColumn('empresa_id')) {
            $this->aplicarAlcanceEmpresa($query, 'Clientes.empresa_id');
        }
        $clientes = $query->all()->toList();

        $this->set(compact('clientes') + $this->datosFiltroEmpresaVista());
    }

    public function view($id = null)
    {
        $cliente = $this->Clientes->get($id, contain: []);
        if (!$this->clienteEnAlcance($cliente)) {
            $this->Flash->error('No tienes acceso a este cliente.');

            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('cliente'));
    }

    public function add()
    {
        $cliente = $this->Clientes->newEmptyEntity();
        $empresaId = $this->empresaIdSesion();
        if ($this->Clientes->getSchema()->hasColumn('empresa_id') && $empresaId !== null) {
            $cliente->empresa_id = $empresaId;
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            if ($this->Clientes->getSchema()->hasColumn('empresa_id')) {
                $data['empresa_id'] = (int)($data['empresa_id'] ?? $empresaId ?? 0);
                if (!$this->entidadEnAlcanceEmpresa((int)$data['empresa_id'])) {
                    $this->Flash->error('Asigna el cliente a una empresa a la que tengas acceso.');
                    $this->set(compact('cliente'));

                    return null;
                }
            }
            $cliente = $this->Clientes->patchEntity($cliente, $data);
            if ($this->Clientes->save($cliente)) {
                $this->Flash->success('Cliente guardado correctamente.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo guardar el cliente. Revisa los datos.');
        }
        $this->set(compact('cliente'));
    }

    public function edit($id = null)
    {
        $cliente = $this->Clientes->get($id, contain: []);
        if (!$this->clienteEnAlcance($cliente)) {
            $this->Flash->error('No tienes acceso a este cliente.');

            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if ($this->Clientes->getSchema()->hasColumn('empresa_id')) {
                $data['empresa_id'] = (int)($data['empresa_id'] ?? $cliente->empresa_id);
                if (!$this->entidadEnAlcanceEmpresa((int)$data['empresa_id'])) {
                    $this->Flash->error('Empresa no permitida.');
                    $this->set(compact('cliente'));

                    return null;
                }
            }
            $cliente = $this->Clientes->patchEntity($cliente, $data);
            if ($this->Clientes->save($cliente)) {
                $this->Flash->success('Cliente actualizado correctamente.');

                return $this->redirect(['action' => 'edit', $cliente->id]);
            }
            $this->Flash->error('No se pudo guardar el cliente. Revisa los datos.');
        }
        $this->set(compact('cliente'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $cliente = $this->Clientes->get($id);
        if (!$this->clienteEnAlcance($cliente)) {
            $this->Flash->error('No tienes acceso a este cliente.');

            return $this->redirect(['action' => 'index']);
        }
        $cliente->eliminado = 1;
        if ($this->Clientes->save($cliente)) {
            $this->Flash->success(__('El cliente ha sido eliminado correctamente'));
            return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('Error al eliminar el cliente. Por favor, intente nuevamente.'));
        return $this->redirect(['action' => 'index']);
    }

    private function clienteEnAlcance(object $cliente): bool
    {
        if (!$this->Clientes->getSchema()->hasColumn('empresa_id')) {
            return true;
        }

        return $this->entidadEnAlcanceEmpresa((int)($cliente->empresa_id ?? 0));
    }
}
