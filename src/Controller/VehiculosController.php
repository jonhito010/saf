<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosVehiculoTable;
use App\Model\Table\VehiculosTable;
use Cake\Collection\Collection;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

class VehiculosController extends AppController
{
    public function index(): void
    {
        $filtroVista = $this->datosFiltroEmpresaVista();
        $table = $this->fetchTable('Vehiculos');
        $query = $table->find()
            ->contain(['DocumentosVehiculo', 'Empresas'])
            ->where(['Vehiculos.estatus' => 'activo']);
        $this->aplicarAlcanceEmpresa($query, 'Vehiculos.empresa_id');

        $buscar = trim((string)$this->request->getQuery('q', ''));
        $tipoCarga = (string)$this->request->getQuery('tipo_carga', '');
        $docEstatus = (string)$this->request->getQuery('doc', '');

        if ($buscar !== '') {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $buscar) . '%';
            $query->where([
                'OR' => [
                    'Vehiculos.placas LIKE' => $term,
                    'Vehiculos.marca LIKE' => $term,
                    'Vehiculos.modelo LIKE' => $term,
                    'Vehiculos.numero_serie LIKE' => $term,
                ],
            ]);
        }
        if ($tipoCarga !== '' && isset(VehiculosTable::tipoCargaOptions()[$tipoCarga])) {
            $query->where(['Vehiculos.tipo_carga' => $tipoCarga]);
        }

        $vehiculos = $query
            ->orderBy([
                'Vehiculos.empresa_id' => 'ASC',
                'Vehiculos.placas' => 'ASC',
            ])
            ->all();

        $estatusMap = [];
        $resumenMap = [];
        foreach ($vehiculos as $v) {
            $estatusMap[$v->id] = VehiculosTable::peorEstatusDocumentos($v->documentos_vehiculo ?? []);
            $resumenMap[$v->id] = VehiculosTable::resumenDocumentos($v->documentos_vehiculo ?? []);
        }

        if ($docEstatus !== '' && in_array($docEstatus, ['vigente', 'por_vencer', 'vencido', 'sin_docs'], true)) {
            $vehiculos = (new Collection($vehiculos))->filter(function ($v) use ($estatusMap, $docEstatus, $resumenMap) {
                if ($docEstatus === 'sin_docs') {
                    return ($resumenMap[$v->id]['total'] ?? 0) === 0;
                }

                return ($estatusMap[$v->id] ?? 'vigente') === $docEstatus;
            })->toList();
        }

        $fleetStats = [
            'total' => count($vehiculos),
            'vigente' => 0,
            'por_vencer' => 0,
            'vencido' => 0,
            'sin_docs' => 0,
        ];
        foreach ($vehiculos as $v) {
            $res = $resumenMap[$v->id] ?? ['total' => 0];
            if ($res['total'] === 0) {
                $fleetStats['sin_docs']++;
                continue;
            }
            match ($estatusMap[$v->id] ?? 'vigente') {
                'vencido' => $fleetStats['vencido']++,
                'por_vencer' => $fleetStats['por_vencer']++,
                default => $fleetStats['vigente']++,
            };
        }

        $this->set([
            'vehiculos' => $vehiculos,
            'estatusMap' => $estatusMap,
            'resumenMap' => $resumenMap,
            'fleetStats' => $fleetStats,
            'tipoCargaOptions' => VehiculosTable::tipoCargaOptions(),
            'filtroQ' => $buscar,
            'filtroTipo' => $tipoCarga,
            'filtroDoc' => $docEstatus,
        ] + $filtroVista);
    }

    public function add(): ?Response
    {
        $vehiculosTable = $this->fetchTable('Vehiculos');
        $vehiculo = $vehiculosTable->newEmptyEntity();

        $empresas = $this->empresasAccesiblesSesion();
        $empresaFija = false;
        $empresaIdUnica = null;
        if (count($empresas) === 1) {
            $empresaIdUnica = (int)array_key_first($empresas);
            $vehiculo->empresa_id = $empresaIdUnica;
            $empresaFija = true;
        } elseif ($empresas !== []) {
            $activa = $this->empresaIdSesion();
            if ($activa !== null && isset($empresas[$activa])) {
                $vehiculo->empresa_id = $activa;
            }
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['estatus'] = $data['estatus'] ?? 'activo';

            if ($empresaIdUnica !== null) {
                $data['empresa_id'] = $empresaIdUnica;
            }
            $empresaPost = (int)($data['empresa_id'] ?? 0);
            if (!$this->entidadEnAlcanceEmpresa($empresaPost)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
                $this->set([
                    'vehiculo' => $vehiculo,
                    'empresas' => $empresas,
                    'tipoCargaOptions' => VehiculosTable::tipoCargaOptions(),
                    'modalidadOptions' => VehiculosTable::modalidadOptions(),
                    'tipoServicioOptions' => VehiculosTable::tipoServicioOptionsPara(
                        is_string($vehiculo->modalidad ?? null) ? $vehiculo->modalidad : null,
                    ),
                    'tipoServicioPorModalidad' => VehiculosTable::tipoServicioPorModalidad(),
                    'requiereCamposOperacion' => VehiculosTable::requiereCamposOperacion(
                        is_string($vehiculo->tipo_carga ?? null) ? $vehiculo->tipo_carga : null,
                    ),
                    'empresaFija' => $empresaFija,
                    'formTitulo' => 'Registrar vehículo',
                ]);

                return null;
            }

            $data = VehiculosTable::sanitizarDatosOperacion($data);
            $vehiculo = $vehiculosTable->patchEntity($vehiculo, $data);
            if ($vehiculosTable->save($vehiculo)) {
                $this->Flash->success('Vehículo registrado.');
                return $this->redirect(['action' => 'view', $vehiculo->id]);
            }
            $this->Flash->error('No se pudo registrar el vehículo. Revisa los datos.');
        }

        $this->set([
            'vehiculo' => $vehiculo,
            'empresas' => $empresas,
            'tipoCargaOptions' => VehiculosTable::tipoCargaOptions(),
            'modalidadOptions' => VehiculosTable::modalidadOptions(),
            'tipoServicioOptions' => VehiculosTable::tipoServicioOptionsPara(
                is_string($vehiculo->modalidad ?? null) ? $vehiculo->modalidad : null,
            ),
            'tipoServicioPorModalidad' => VehiculosTable::tipoServicioPorModalidad(),
            'requiereCamposOperacion' => VehiculosTable::requiereCamposOperacion(
                is_string($vehiculo->tipo_carga ?? null) ? $vehiculo->tipo_carga : null,
            ),
            'empresaFija' => $empresaFija,
            'formTitulo' => 'Registrar vehículo',
        ]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $vehiculosTable = $this->fetchTable('Vehiculos');
        try {
            $vehiculo = $vehiculosTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$vehiculo->empresa_id)) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $empresas = $this->empresasAccesiblesSesion();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = VehiculosTable::sanitizarDatosOperacion($this->request->getData());
            $empresaPost = (int)($data['empresa_id'] ?? $vehiculo->empresa_id);
            if (!$this->entidadEnAlcanceEmpresa($empresaPost)) {
                $this->Flash->error('No tienes acceso a esa empresa.');
            } else {
                $vehiculo = $vehiculosTable->patchEntity($vehiculo, $data);
                if ($vehiculosTable->save($vehiculo)) {
                    $this->Flash->success('Vehículo actualizado.');
                    return $this->redirect(['action' => 'view', $vehiculo->id]);
                }
                $this->Flash->error('No se pudo actualizar el vehículo. Revisa los datos.');
            }
        }

        $this->set([
            'vehiculo' => $vehiculo,
            'empresas' => $empresas,
            'tipoCargaOptions' => VehiculosTable::tipoCargaOptions(),
            'modalidadOptions' => VehiculosTable::modalidadOptions(),
            'tipoServicioOptions' => VehiculosTable::tipoServicioOptionsPara(
                is_string($vehiculo->modalidad ?? null) ? $vehiculo->modalidad : null,
            ),
            'tipoServicioPorModalidad' => VehiculosTable::tipoServicioPorModalidad(),
            'requiereCamposOperacion' => VehiculosTable::requiereCamposOperacion(
                is_string($vehiculo->tipo_carga ?? null) ? $vehiculo->tipo_carga : null,
            ),
            'empresaFija' => false,
            'formTitulo' => 'Editar vehículo',
        ]);

        return null;
    }

    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $vehiculosTable = $this->fetchTable('Vehiculos');

        try {
            $vehiculo = $vehiculosTable->get($id);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$vehiculo->empresa_id)) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $vehiculo->estatus = 'inactivo';
        if ($vehiculosTable->save($vehiculo)) {
            $this->Flash->success('Vehículo dado de baja (inactivado).');
        } else {
            $this->Flash->error('No se pudo dar de baja el vehículo.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function view(int $id): ?Response
    {
        try {
            $vehiculo = $this->fetchTable('Vehiculos')
                ->get($id, contain: ['DocumentosVehiculo', 'Mantenimientos', 'Empresas']);
        } catch (RecordNotFoundException) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->entidadEnAlcanceEmpresa((int)$vehiculo->empresa_id)) {
            $this->Flash->error('Vehículo no encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $tiposMp = array_keys(DocumentosVehiculoTable::tiposMaterialesPeligrosos());
        $todosDocs = $vehiculo->documentos_vehiculo ?? [];
        $docsGeneral = [];
        $docsMp = [];
        foreach ($todosDocs as $doc) {
            if (in_array((string)$doc->tipo_documento, $tiposMp, true)) {
                $docsMp[] = $doc;
            } else {
                $docsGeneral[] = $doc;
            }
        }

        $sortDocs = static fn(array $lista): array => (new Collection($lista))
            ->sortBy(fn($d) => DocumentosVehiculoTable::tipoLabel((string)$d->tipo_documento))
            ->toList();

        $docsOrdenados = $sortDocs($docsGeneral);
        $docsOrdenadosMp = $sortDocs($docsMp);

        $tabInicial = (string)$this->request->getQuery('tab', 'docs');
        if (!in_array($tabInicial, ['docs', 'mp', 'mant', 'hist'], true)) {
            $tabInicial = 'docs';
        }

        $this->set([
            'vehiculo' => $vehiculo,
            'docsOrdenados' => $docsOrdenados,
            'docsOrdenadosMp' => $docsOrdenadosMp,
            'docResumen' => VehiculosTable::resumenDocumentos($docsGeneral),
            'docResumenMp' => VehiculosTable::resumenDocumentos($docsMp),
            'peorEstatus' => VehiculosTable::peorEstatusDocumentos($docsGeneral),
            'peorEstatusMp' => VehiculosTable::peorEstatusDocumentos($docsMp),
            'docsRequeridos' => VehiculosTable::documentosRequeridos(),
            'docsRequeridosMp' => DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos(),
            'tiposMp' => $tiposMp,
            'catalogoMp' => DocumentosVehiculoTable::catalogoMaterialesPeligrosos(),
            'esMaterialPeligroso' => VehiculosTable::esMaterialPeligroso($vehiculo->tipo_carga ?? null),
            'tabInicial' => $tabInicial,
        ]);

        return null;
    }
}
