<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;

class CatalogoMercanciasController extends AppController
{
    public function index(): void
    {
        $query = $this->fetchTable('CatalogoMercancias')
            ->find()
            ->orderBy(['descripcion' => 'ASC']);

        $mercancias = $this->paginate($query, ['limit' => 40, 'maxLimit' => 200]);
        $this->set(compact('mercancias'));
    }

    public function add(): ?Response
    {
        $table = $this->fetchTable('CatalogoMercancias');
        $mercancia = $table->newEmptyEntity();

        if ($this->request->is('post')) {
            $mercancia = $table->patchEntity($mercancia, $this->request->getData());
            $mercancia->material_peligroso = (bool)($this->request->getData('material_peligroso'));
            if ($table->save($mercancia)) {
                $this->Flash->success('Mercancía registrada en el catálogo.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo guardar. Revisa los campos.');
        }

        $this->set(compact('mercancia'));
        return null;
    }

    public function edit(int $id): ?Response
    {
        $table = $this->fetchTable('CatalogoMercancias');
        try {
            $mercancia = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Mercancía no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $mercancia = $table->patchEntity($mercancia, $this->request->getData());
            $mercancia->material_peligroso = (bool)($this->request->getData('material_peligroso'));
            if ($table->save($mercancia)) {
                $this->Flash->success('Mercancía actualizada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('No se pudo actualizar. Revisa los campos.');
        }

        $this->set(compact('mercancia'));
        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('CatalogoMercancias');
        try {
            $mercancia = $table->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Mercancía no encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if ($table->delete($mercancia)) {
            $this->Flash->success('Mercancía eliminada del catálogo.');
        } else {
            $this->Flash->error('No se pudo eliminar la mercancía.');
        }
        return $this->redirect(['action' => 'index']);
    }

    public function autocomplete(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q', ''));

        $conditions = [];
        if ($q !== '') {
            $conditions['OR'] = [
                'descripcion LIKE'  => '%' . $q . '%',
                'bienes_transp LIKE' => $q . '%',
            ];
        }

        $rows = $this->fetchTable('CatalogoMercancias')
            ->find()
            ->select(['id', 'bienes_transp', 'descripcion', 'clave_unidad',
                      'peso_en_kg', 'material_peligroso', 'cve_material_peligroso', 'embalaje'])
            ->where($conditions)
            ->orderBy(['descripcion' => 'ASC'])
            ->limit(20)
            ->all()
            ->toArray();

        $out = array_map(static fn($r) => [
            'id'                     => $r->id,
            'bienes_transp'          => $r->bienes_transp,
            'descripcion'            => $r->descripcion,
            'clave_unidad'           => $r->clave_unidad,
            'peso_en_kg'             => $r->peso_en_kg,
            'material_peligroso'     => (bool)$r->material_peligroso,
            'cve_material_peligroso' => $r->cve_material_peligroso ?? '',
            'embalaje'               => $r->embalaje ?? '',
            'label'                  => $r->bienes_transp . ' — ' . $r->descripcion,
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    /* Autocomplete SAT c_ClaveUnidad desde tabla unidades */
    public function autocompleteUnidad(): Response
    {
        $this->request->allowMethod(['get']);
        $q  = trim((string)$this->request->getQuery('q', ''));
        $cn = ConnectionManager::get('default');

        $cols    = $cn->execute("SHOW COLUMNS FROM `unidades`")->fetchAll('assoc');
        $names   = array_column($cols, 'Field');
        $keyCol  = in_array('id',          $names) ? 'id'          : ($names[0] ?? 'id');
        $descCol = in_array('descripcion', $names) ? 'descripcion' : (in_array('nombre', $names) ? 'nombre' : $keyCol);

        $params = [];
        $where  = "{$keyCol} IS NOT NULL AND {$keyCol} <> ''";
        if ($q !== '') {
            $where   .= " AND ({$keyCol} LIKE ? OR {$descCol} LIKE ?)";
            $params[] = $q . '%';
            $params[] = '%' . $q . '%';
        }

        $rows = $cn->execute(
            "SELECT {$keyCol} AS clave, {$descCol} AS descripcion
             FROM `unidades`
             WHERE {$where}
             ORDER BY {$keyCol}
             LIMIT 30",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'id'          => (string)($r['clave'] ?? ''),
            'descripcion' => (string)($r['descripcion'] ?? ''),
            'nombre'      => (string)($r['descripcion'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }

    /* Autocomplete SAT c_ClaveProdServCP desde tabla cpprodserv */
    public function autocompleteBienes(): Response
    {
        $this->request->allowMethod(['get']);
        $q  = trim((string)$this->request->getQuery('q', ''));
        $cn = ConnectionManager::get('default');

        /* Detectar columnas de texto disponibles */
        $cols   = $cn->execute("SHOW COLUMNS FROM `cpprodserv`")->fetchAll('assoc');
        $names  = array_column($cols, 'Field');

        $keyCol  = in_array('id',          $names) ? 'id'          : ($names[0] ?? 'id');
        $descCol = in_array('descripcion', $names) ? 'descripcion' : (in_array('nombre', $names) ? 'nombre' : $keyCol);

        $params = [];
        $where  = "{$keyCol} IS NOT NULL AND {$keyCol} <> ''";
        if ($q !== '') {
            $where   .= " AND ({$keyCol} LIKE ? OR {$descCol} LIKE ?)";
            $params[] = $q . '%';
            $params[] = '%' . $q . '%';
        }

        $rows = $cn->execute(
            "SELECT {$keyCol} AS clave, {$descCol} AS descripcion
             FROM `cpprodserv`
             WHERE {$where}
             ORDER BY {$keyCol}
             LIMIT 30",
            $params
        )->fetchAll('assoc');

        $out = array_map(static fn($r) => [
            'id'          => (string)($r['clave'] ?? ''),
            'descripcion' => (string)($r['descripcion'] ?? ''),
            'nombre'      => (string)($r['descripcion'] ?? ''),
        ], $rows);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($out, JSON_UNESCAPED_UNICODE));
    }
}
