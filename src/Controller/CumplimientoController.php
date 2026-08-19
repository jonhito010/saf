<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Model\Table\VehiculosTable;
use App\Utility\CumplimientoMaterialesPeligrosos;

class CumplimientoController extends AppController
{
    public function materialesPeligrosos(): void
    {
        $empresaIds = $this->empresaIdsAlcance();
        $filtroVista = $this->datosFiltroEmpresaVista();
        $docsAdic = [];
        $tipos = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());
        if ($tipos !== [] && $empresaIds !== []) {
            $docsAdic = $this->fetchTable('DocumentosEmpresa')
                ->find()
                ->where([
                    'empresa_id IN' => $empresaIds,
                    'tipo_documento IN' => $tipos,
                ])
                ->all();
        }
        $docsAdicPorTipo = [];
        foreach ($docsAdic as $d) {
            // En vista consolidada puede haber varios docs del mismo tipo; se conserva el primero / peor se evalúa en UI por tipo.
            if (!isset($docsAdicPorTipo[$d->tipo_documento])) {
                $docsAdicPorTipo[$d->tipo_documento] = $d;
            }
        }

        $vehiculosMp = $this->fetchTable('Vehiculos')
            ->find()
            ->contain(['DocumentosVehiculo', 'Empresas'])
            ->where(['Vehiculos.estatus' => 'activo'])
            ->where(['Vehiculos.tipo_carga' => 'material_peligroso']);
        $this->aplicarAlcanceEmpresa($vehiculosMp, 'Vehiculos.empresa_id');
        $vehiculosMp = $vehiculosMp->all();

        $reqMp = DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos();
        $vehiculosResumen = [];
        foreach ($vehiculosMp as $v) {
            $porTipo = [];
            foreach ($v->documentos_vehiculo as $doc) {
                $porTipo[$doc->tipo_documento] = $doc;
            }
            $ok = 0;
            foreach ($reqMp as $tipo) {
                if (isset($porTipo[$tipo]) && in_array($porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $vehiculosResumen[] = [
                'vehiculo' => $v,
                'pct' => count($reqMp) > 0 ? (int)round(100 * $ok / count($reqMp)) : 0,
                'docs' => $porTipo,
            ];
        }

        $operadores = $this->fetchTable('Operadores')
            ->find()
            ->contain(['DocumentosOperador', 'Empresas'])
            ->where(['Operadores.estatus' => 'activo']);
        $this->aplicarAlcanceEmpresa($operadores, 'Operadores.empresa_id');
        $operadores = $operadores->all();

        $tiposOpMp = DocumentosOperadorTable::tiposRequeridosMaterialesPeligrosos();
        $operadoresResumen = [];
        foreach ($operadores as $op) {
            $porTipo = [];
            foreach ($op->documentos_operador as $doc) {
                $porTipo[$doc->tipo_documento] = $doc;
            }
            $ok = 0;
            foreach ($tiposOpMp as $tipo) {
                if (isset($porTipo[$tipo]) && in_array($porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $operadoresResumen[] = [
                'operador' => $op,
                'pct' => count($tiposOpMp) > 0 ? (int)round(100 * $ok / count($tiposOpMp)) : 0,
                'docs' => $porTipo,
            ];
        }

        $desde = new \DateTimeImmutable('-6 months');
        $comprobantesTable = $this->fetchTable('Comprobantes');
        $schemaCp = $comprobantesTable->getSchema();
        $qCp = $comprobantesTable->find();
        if ($empresaIds !== [] && $schemaCp->hasColumn('empresa_id')) {
            $qCp->where(['empresa_id IN' => $empresaIds]);
        } elseif ($empresaIds !== [] && $schemaCp->hasColumn('rfcEmisor')) {
            $empresas = $this->fetchTable('Empresas')
                ->find()
                ->select(['rfc'])
                ->where(['id IN' => $empresaIds])
                ->all();
            $rfcs = [];
            foreach ($empresas as $emp) {
                $rfc = trim((string)($emp->rfc ?? ''));
                if ($rfc !== '') {
                    $rfcs[] = $rfc;
                }
            }
            if ($rfcs !== []) {
                $qCp->where(['rfcEmisor IN' => $rfcs]);
            } else {
                $qCp->where(['0 = 1']);
            }
        } elseif ($empresaIds === []) {
            $qCp->where(['0 = 1']);
        }
        if ($schemaCp->hasColumn('fechaEmision')) {
            $qCp->where(['fechaEmision >=' => $desde->format('Y-m-d')]);
        } elseif ($schemaCp->hasColumn('created')) {
            $qCp->where(['created >=' => $desde->format('Y-m-d H:i:s')]);
        }
        if ($schemaCp->hasColumn('llevaCartaPorte')) {
            $qCp->where(['llevaCartaPorte' => 1]);
        }
        $comprobantes6m = $qCp->count();

        $this->set([
            'secciones' => CumplimientoMaterialesPeligrosos::requisitosPorSeccion(),
            'docsAdicPorTipo' => $docsAdicPorTipo,
            'vehiculosResumen' => $vehiculosResumen,
            'operadoresResumen' => $operadoresResumen,
            'comprobantes6m' => $comprobantes6m,
            'reqVehiculo' => $reqMp,
            'reqOperador' => $tiposOpMp,
        ] + $filtroVista);
    }

    public function index(): void
    {
        $empresaIds = $this->empresaIdsAlcance();
        $filtroVista = $this->datosFiltroEmpresaVista();

        $vehiculosQuery = $this->fetchTable('Vehiculos')
            ->find()->contain(['DocumentosVehiculo', 'Empresas'])
            ->where(['Vehiculos.estatus' => 'activo'])
            ->orderBy(['Vehiculos.placas' => 'ASC']);
        $this->aplicarAlcanceEmpresa($vehiculosQuery, 'Vehiculos.empresa_id');
        $vehiculos = $vehiculosQuery->all();

        $operadoresQuery = $this->fetchTable('Operadores')
            ->find()->contain(['DocumentosOperador', 'Empresas'])
            ->where(['Operadores.estatus' => 'activo'])
            ->orderBy(['Operadores.apellido_paterno' => 'ASC']);
        $this->aplicarAlcanceEmpresa($operadoresQuery, 'Operadores.empresa_id');
        $operadores = $operadoresQuery->all();

        $docVehiculo = $this->fetchTable('DocumentosVehiculo');
        $docOperador = $this->fetchTable('DocumentosOperador');
        $docEmpresa = $this->fetchTable('DocumentosEmpresa');

        $tiposVehReq = VehiculosTable::documentosRequeridos();
        $vehiculosCumpl = [];
        foreach ($vehiculos as $v) {
            $porTipo = [];
            foreach ($v->documentos_vehiculo as $d) {
                $porTipo[$d->tipo_documento] = $d;
            }
            $ok = 0;
            foreach ($tiposVehReq as $tipo) {
                if (isset($porTipo[$tipo]) && in_array($porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $pct = count($tiposVehReq) > 0 ? (int)round(($ok / count($tiposVehReq)) * 100) : 0;
            $vehiculosCumpl[] = ['vehiculo' => $v, 'pct' => $pct];
        }

        $operadoresCumpl = [];
        $tiposReq = DocumentosOperadorTable::tiposRequeridos();
        foreach ($operadores as $op) {
            $docsPorTipo = [];
            foreach ($op->documentos_operador as $d) {
                $docsPorTipo[$d->tipo_documento] = $d->estatus;
            }
            $ok = 0;
            foreach ($tiposReq as $tipo) {
                if (isset($docsPorTipo[$tipo]) && in_array($docsPorTipo[$tipo], ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $pct = round(($ok / count($tiposReq)) * 100);
            $operadoresCumpl[] = ['operador' => $op, 'pct' => $pct];
        }

        $docsVencidosQuery = $docVehiculo->find()
            ->contain(['Vehiculos'])
            ->where(['DocumentosVehiculo.estatus IN' => ['vencido', 'por_vencer']])
            ->orderBy(['DocumentosVehiculo.estatus' => 'ASC', 'DocumentosVehiculo.fecha_vencimiento' => 'ASC']);
        if ($empresaIds !== []) {
            $docsVencidosQuery->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]));
        } else {
            $docsVencidosQuery->where(['0 = 1']);
        }
        $docsVencidos = $docsVencidosQuery->all();

        $docsOpVencidosQuery = $docOperador->find()
            ->contain(['Operadores'])
            ->where(['DocumentosOperador.estatus IN' => ['vencido', 'por_vencer']])
            ->orderBy(['DocumentosOperador.estatus' => 'ASC']);
        if ($empresaIds !== []) {
            $docsOpVencidosQuery->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]));
        } else {
            $docsOpVencidosQuery->where(['0 = 1']);
        }
        $docsOpVencidos = $docsOpVencidosQuery->all();

        $docsEmpVencidosQuery = $docEmpresa->find()
            ->contain(['Empresas'])
            ->where(['DocumentosEmpresa.estatus IN' => ['vencido', 'por_vencer']]);
        $this->aplicarAlcanceEmpresa($docsEmpVencidosQuery, 'DocumentosEmpresa.empresa_id');
        $docsEmpVencidos = $docsEmpVencidosQuery->all();

        $this->set([
            'vehiculosCumpl' => $vehiculosCumpl,
            'operadoresCumpl' => $operadoresCumpl,
            'docsVencidos' => $docsVencidos,
            'docsOpVencidos' => $docsOpVencidos,
            'docsEmpVencidos' => $docsEmpVencidos,
            'requisitosFlota' => DocumentosVehiculoTable::catalogoCumplimientoNormativoFlota(),
            'notaRetencionFlota' => DocumentosVehiculoTable::notaRetencionFlotaPorcentaje(),
        ] + $filtroVista);
    }
}
