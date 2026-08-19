<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Servicios Controller
 *
 * @property \App\Model\Table\ServiciosTable $Servicios
 * @method \App\Model\Entity\Servicio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ServiciosController extends AppController
{
    private function redirectIndex(): \Cake\Http\Response
    {
        return $this->redirect('/facturacion/servicios');
    }

    /**
     * Búsqueda de clave SAT para autocompletar (JSON)
     * GET /servicios/search-clave-sat?q=texto
     */
    public function searchClaveSAT()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;
        
        $q = (string)($this->request->getQuery('q') ?? '');

        $results = [];
        if ($q !== '') {
            $claveprods = $this->fetchTable('Claveprods')->find()
                ->select(['id', 'nombre'])
                ->where([
                    'OR' => [
                        'id LIKE' => "%{$q}%",
                        'nombre LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('id')
                ->limit(10)
                ->all();

            foreach ($claveprods as $c) {
                $label = $c->id ?? '';
                if (!empty($c->nombre)) {
                    $label .= " - " . ($c->nombre ?? '');
                }
                
                $results[] = [
                    'id' => $c->id ?? '',
                    'label' => trim($label),
                    'nombre' => $c->nombre ?? '',
                ];
            }
        }

        $response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));

        return $response;
    }

    /**
     * Búsqueda de unidad SAT para autocompletar (JSON)
     * GET /servicios/search-unidad-sat?q=texto
     */
    public function searchUnidadSAT()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;
        
        $q = (string)($this->request->getQuery('q') ?? '');

        $results = [];
        if ($q !== '') {
            $unidades = $this->fetchTable('Unidades')->find()
                ->select(['id', 'nombre'])
                ->where([
                    'OR' => [
                        'id LIKE' => "%{$q}%",
                        'nombre LIKE' => "%{$q}%",
                    ],
                ])
                ->orderByAsc('id')
                ->limit(10)
                ->all();

            foreach ($unidades as $u) {
                $label = $u->id ?? '';
                if (!empty($u->nombre)) {
                    $label .= " - " . ($u->nombre ?? '');
                }
                
                $results[] = [
                    'id' => $u->id ?? '',
                    'label' => trim($label),
                    'nombre' => $u->nombre ?? '',
                ];
            }
        }

        $response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));

        return $response;
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Servicios->find()
            ->where(['Servicios.eliminado' => 0])
            ->orderByAsc('Servicios.descripcion');
        if ($this->Servicios->getSchema()->hasColumn('empresa_id')) {
            $this->aplicarAlcanceEmpresa($query, 'Servicios.empresa_id');
        }
        $servicios = $query->all()->toList();

        $this->set(compact('servicios') + $this->datosFiltroEmpresaVista());
    }

    public function view($id = null)
    {
        $servicio = $this->Servicios->get($id, contain: []);
        if (!$this->servicioEnAlcance($servicio)) {
            $this->Flash->error('No tienes acceso a este servicio.');

            return $this->redirectIndex();
        }

        $this->set(compact('servicio'));
    }

    public function add()
    {
        $servicio = $this->Servicios->newEmptyEntity();
        $empresaId = $this->empresaIdSesion();
        if ($this->Servicios->getSchema()->hasColumn('empresa_id') && $empresaId !== null) {
            $servicio->empresa_id = $empresaId;
        }
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['eliminado'] = 0;
            if ($this->Servicios->getSchema()->hasColumn('empresa_id')) {
                $data['empresa_id'] = (int)($data['empresa_id'] ?? $empresaId ?? 0);
                if (!$this->entidadEnAlcanceEmpresa((int)$data['empresa_id'])) {
                    $this->Flash->error('Asigna el servicio a una empresa a la que tengas acceso.');
                    $this->set(compact('servicio'));

                    return null;
                }
            }
            $servicio = $this->Servicios->patchEntity($servicio, $data);
            if ($this->Servicios->save($servicio)) {
                $this->Flash->success('Servicio guardado correctamente.');

                return $this->redirectIndex();
            }
            $this->Flash->error('No se pudo guardar el servicio. Revisa los datos.');
        }
        $this->set(compact('servicio'));
    }

    public function edit($id = null)
    {
        $servicio = $this->Servicios->get($id, contain: []);
        if (!$this->servicioEnAlcance($servicio)) {
            $this->Flash->error('No tienes acceso a este servicio.');

            return $this->redirectIndex();
        }
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if ($this->Servicios->getSchema()->hasColumn('empresa_id')) {
                $data['empresa_id'] = (int)($data['empresa_id'] ?? $servicio->empresa_id);
                if (!$this->entidadEnAlcanceEmpresa((int)$data['empresa_id'])) {
                    $this->Flash->error('Empresa no permitida.');
                    $this->set(compact('servicio'));

                    return null;
                }
            }
            $servicio = $this->Servicios->patchEntity($servicio, $data);
            if ($this->Servicios->save($servicio)) {
                $this->Flash->success('Servicio guardado correctamente.');

                return $this->redirectIndex();
            }
            $this->Flash->error('No se pudo guardar el servicio. Revisa los datos.');
        }
        $this->set(compact('servicio'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $servicio = $this->Servicios->get($id);
        if (!$this->servicioEnAlcance($servicio)) {
            $this->Flash->error('No tienes acceso a este servicio.');

            return $this->redirectIndex();
        }
        $servicio->eliminado = 1;
        if ($this->Servicios->save($servicio)) {
            $this->Flash->success('Servicio eliminado correctamente.');
        } else {
            $this->Flash->error('No se pudo eliminar el servicio.');
        }

        return $this->redirectIndex();
    }

    private function servicioEnAlcance(object $servicio): bool
    {
        if (!$this->Servicios->getSchema()->hasColumn('empresa_id')) {
            return true;
        }

        return $this->entidadEnAlcanceEmpresa((int)($servicio->empresa_id ?? 0));
    }
}
