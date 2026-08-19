<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Service\AlertasReglasService;
use App\Service\CumplimientoService;
use Cake\Http\Exception\ForbiddenException;

class DashboardController extends AppController
{
    public function index(): void
    {
        $this->cargarYRenderizarPanel(false);
    }

    /**
     * Panel general administrador: cumplimiento consolidado de todas las empresas
     * accesibles (mismo look del panel de control, sin filtro a una sola empresa).
     */
    public function general(): void
    {
        if (!$this->esAdministrador()) {
            throw new ForbiddenException('Solo administradores pueden ver el panel general de todas las empresas.');
        }
        $this->cargarYRenderizarPanel(true);
    }

    private function cargarYRenderizarPanel(bool $panelGeneral): void
    {
        // Panel general: siempre todas las accesibles. Panel normal: respeta ?empresa_id=.
        $empresaIds = $panelGeneral
            ? $this->empresaIdsConsulta()
            : $this->empresaIdsAlcance();
        $filtroVista = $panelGeneral
            ? [
                'empresasFiltro' => $this->empresasAccesiblesSesion(),
                'filtroEmpresaId' => null,
                'vistaConsolidada' => true,
                'alcanceLabel' => 'Todas las empresas (' . count($empresaIds) . ')',
            ]
            : $this->datosFiltroEmpresaVista();
        $empresaId = $this->empresaIdSesion();
        $vehiculosTable = $this->fetchTable('Vehiculos');
        $operadoresTable = $this->fetchTable('Operadores');
        $docVehiculo = $this->fetchTable('DocumentosVehiculo');
        $docEmpresa = $this->fetchTable('DocumentosEmpresa');
        $docOperador = $this->fetchTable('DocumentosOperador');
        $alertas = $this->fetchTable('Alertas');

        $this->sincronizarAlertasEmpresasAccesibles();

        $vehActivos = $vehiculosTable->find()->where(['Vehiculos.estatus' => 'activo']);
        $opActivos = $operadoresTable->find()->where(['Operadores.estatus' => 'activo']);
        if ($empresaIds === []) {
            $vehActivos->where(['0 = 1']);
            $opActivos->where(['0 = 1']);
        } else {
            $vehActivos->where(['Vehiculos.empresa_id IN' => $empresaIds]);
            $opActivos->where(['Operadores.empresa_id IN' => $empresaIds]);
        }

        $totalVehiculos = $vehActivos->count();
        $totalOperadores = $opActivos->count();

        $alertasQuery = $alertas->find()->where(['resuelto' => 0]);
        if ($empresaIds === []) {
            $alertasQuery->where(['0 = 1']);
        } else {
            $alertasQuery->where(['Alertas.empresa_id IN' => $empresaIds]);
        }
        $totalAlertas = $alertasQuery->count();
        $alertasCriticas = (clone $alertasQuery)->where(['nivel_urgencia' => 'critico'])->count();
        $alertasAdvertencia = (clone $alertasQuery)->where(['nivel_urgencia' => 'advertencia'])->count();

        $vehiculosConAlerta = $vehiculosTable->find()
            ->innerJoinWith('DocumentosVehiculo', fn($q) =>
                $q->where(['DocumentosVehiculo.estatus IN' => ['vencido', 'por_vencer']]))
            ->distinct(['Vehiculos.id']);
        if ($empresaIds === []) {
            $vehiculosConAlerta->where(['0 = 1']);
        } else {
            $vehiculosConAlerta->where(['Vehiculos.empresa_id IN' => $empresaIds]);
        }
        $vehiculosConAlerta = $vehiculosConAlerta->count();

        $docEmpresaQ = $docEmpresa->find();
        $docVehQ = $docVehiculo->find();
        $docOpQ = $docOperador->find();
        if ($empresaIds === []) {
            $docEmpresaQ->where(['0 = 1']);
            $docVehQ->where(['0 = 1']);
            $docOpQ->where(['0 = 1']);
        } else {
            $docEmpresaQ->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds]);
            $docVehQ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]));
            $docOpQ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]));
        }

        $cumplimientoSvc = new CumplimientoService();
        $cumplimiento = $cumplimientoSvc->porcentajeConsolidado($empresaIds);
        $indicadoresGlobales = $this->calcularIndicadoresGlobales($empresaIds, $cumplimientoSvc);

        $mpStats = $this->calcularEstadisticasMp(
            $vehiculosTable,
            $operadoresTable,
            $docVehiculo,
            $docEmpresa,
            $docOperador,
            $empresaIds,
        );

        $vencimientosPróximos = $this->armarVencimientosProximos(
            $docEmpresa,
            $docVehiculo,
            $docOperador,
            $empresaIds,
        );

        $alertasRecientes = $alertas->find()
            ->where(['resuelto' => 0]);
        if ($empresaIds === []) {
            $alertasRecientes->where(['0 = 1']);
        } else {
            $alertasRecientes->where(['Alertas.empresa_id IN' => $empresaIds]);
        }
        $alertasRecientes = $alertasRecientes
            ->orderBy(['Alertas.created_at' => 'DESC', 'Alertas.id' => 'DESC'])
            ->limit(6)
            ->all();

        $empresasResumen = $this->armarResumenEmpresas(
            $vehiculosTable,
            $operadoresTable,
            $docEmpresa,
            $docVehiculo,
            $docOperador,
            $alertas,
        );

        $totalEmpresasCuenta = count($this->empresaIdsConsulta());
        $empresaNombre = $filtroVista['alcanceLabel'];
        $puedeNotificar = $this->esAdministrador();

        $this->set(compact(
            'totalVehiculos',
            'totalOperadores',
            'cumplimiento',
            'indicadoresGlobales',
            'totalAlertas',
            'alertasCriticas',
            'alertasAdvertencia',
            'vehiculosConAlerta',
            'vencimientosPróximos',
            'alertasRecientes',
            'mpStats',
            'empresaNombre',
            'empresaId',
            'totalEmpresasCuenta',
            'empresasResumen',
            'panelGeneral',
            'puedeNotificar',
        ) + $filtroVista);

        $this->render('index');
    }

    /**
     * @param list<int> $empresaIds
     * @return array{vigente_pct: int, por_vencer_pct: int, vencido_pct: int, total: int}
     */
    private function calcularIndicadoresGlobales(array $empresaIds, CumplimientoService $svc): array
    {
        $vigentes = 0;
        $porVencer = 0;
        $vencidos = 0;

        foreach ($empresaIds as $eid) {
            $resumen = $svc->resumenEmpresa((int)$eid);
            foreach ($resumen['desglose'] as $item) {
                $estatus = (string)($item['estatus'] ?? '');
                if ($estatus === 'vigente') {
                    $vigentes++;
                } elseif ($estatus === 'por_vencer') {
                    $porVencer++;
                } elseif ($estatus === 'vencido') {
                    $vencidos++;
                }
            }
        }

        $total = $vigentes + $porVencer + $vencidos;
        if ($total <= 0) {
            return [
                'vigente_pct' => 0,
                'por_vencer_pct' => 0,
                'vencido_pct' => 0,
                'total' => 0,
            ];
        }

        return [
            'vigente_pct' => (int)round(($vigentes / $total) * 100),
            'por_vencer_pct' => (int)round(($porVencer / $total) * 100),
            'vencido_pct' => (int)round(($vencidos / $total) * 100),
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function enriquecerFilaVencimiento(array $row, mixed $fechaRaw): array
    {
        $dias = AlertasReglasService::diasRestantes($fechaRaw);
        $vencido = ($row['estatus'] ?? '') === 'vencido' || ($dias !== null && $dias <= 0);
        $row['dias_restantes'] = $dias;
        $row['tipo_label'] = (string)($row['documento'] ?? $row['tipo_label'] ?? '');
        if ($vencido) {
            $row['dias_texto'] = 'Vencido';
            $row['dias_clase'] = 'alerta-dias--critico';
        } elseif ($dias === null) {
            $row['dias_texto'] = 'Sin fecha';
            $row['dias_clase'] = 'alerta-dias--warn';
        } elseif ($dias === 1) {
            $row['dias_texto'] = 'Falta 1 día';
            $row['dias_clase'] = $dias <= 15 ? 'alerta-dias--critico' : ($dias <= 30 ? 'alerta-dias--warn' : 'alerta-dias--ok');
        } else {
            $row['dias_texto'] = 'Faltan ' . $dias . ' días';
            $row['dias_clase'] = $dias <= 15 ? 'alerta-dias--critico' : ($dias <= 30 ? 'alerta-dias--warn' : 'alerta-dias--ok');
        }

        return $row;
    }

    /**
     * Resumen de cumplimiento por cada empresa accesible.
     *
     * @return list<array<string, mixed>>
     */
    private function armarResumenEmpresas(
        $vehiculosTable,
        $operadoresTable,
        $docEmpresa,
        $docVehiculo,
        $docOperador,
        $alertas,
    ): array {
        $empresasMap = $this->empresasAccesiblesSesion();
        if ($empresasMap === []) {
            return [];
        }

        $activaId = $this->empresaIdSesion();
        $empresasTable = $this->fetchTable('Empresas');
        $ids = array_map('intval', array_keys($empresasMap));
        $rfcs = [];
        $contactos = [];
        try {
            foreach ($empresasTable->find()->select(['id', 'rfc', 'telefono', 'email'])->where(['id IN' => $ids])->all() as $e) {
                $eidContacto = (int)$e->id;
                $rfcs[$eidContacto] = strtoupper(trim((string)($e->rfc ?? '')));
                $contactos[$eidContacto] = [
                    'telefono' => trim((string)($e->telefono ?? '')),
                    'email' => trim((string)($e->email ?? '')),
                ];
            }
        } catch (\Throwable) {
            $rfcs = [];
            $contactos = [];
        }

        $tiposMpVeh = DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos();
        $tiposMpOp = DocumentosOperadorTable::tiposRequeridosMaterialesPeligrosos();
        $tiposAdic = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());
        $cumplimientoSvc = new CumplimientoService();

        $resumen = [];
        foreach ($empresasMap as $eid => $nombre) {
            $eid = (int)$eid;
            $veh = $vehiculosTable->find()
                ->where(['Vehiculos.empresa_id' => $eid, 'Vehiculos.estatus' => 'activo'])
                ->count();
            $op = $operadoresTable->find()
                ->where(['Operadores.empresa_id' => $eid, 'Operadores.estatus' => 'activo'])
                ->count();
            $alQ = $alertas->find()->where(['Alertas.empresa_id' => $eid, 'resuelto' => 0]);
            $alTotal = (clone $alQ)->count();
            $alCrit = (clone $alQ)->where(['nivel_urgencia' => 'critico'])->count();

            $cumpl = $cumplimientoSvc->resumenEmpresa($eid);
            $pct = $cumpl['porcentaje'];
            $totalDocs = $cumpl['total'];
            $correctos = $cumpl['ok'];
            $faltantes = $cumpl['faltantes'];
            $porVencer = 0;
            $vencidos = 0;
            foreach ($cumpl['desglose'] as $item) {
                if ($item['estatus'] === 'por_vencer') {
                    $porVencer++;
                } elseif ($item['estatus'] === 'vencido') {
                    $vencidos++;
                }
            }

            // Vencidos / por vencer (lista corta para el panel)
            $vencidosLista = $this->listarDocsEmpresaPorEstatus(
                $docEmpresa,
                $docVehiculo,
                $docOperador,
                $eid,
                ['vencido', 'por_vencer'],
                8,
            );

            // Materiales peligrosos por empresa
            $vehMp = $vehiculosTable->find()
                ->contain(['DocumentosVehiculo'])
                ->where([
                    'Vehiculos.empresa_id' => $eid,
                    'Vehiculos.estatus' => 'activo',
                    'Vehiculos.tipo_carga' => 'material_peligroso',
                ])
                ->all();
            $vehMpTotal = $vehMp->count();
            $vehMpOk = 0;
            $sumPctVehMp = 0;
            $vencidosMp = [];
            foreach ($vehMp as $v) {
                $porTipo = [];
                foreach ($v->documentos_vehiculo ?? [] as $doc) {
                    $porTipo[(string)$doc->tipo_documento] = $doc;
                }
                $ok = 0;
                foreach ($tiposMpVeh as $tipo) {
                    if (isset($porTipo[$tipo]) && in_array((string)$porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                        $ok++;
                    }
                    if (isset($porTipo[$tipo]) && in_array((string)$porTipo[$tipo]->estatus, ['vencido', 'por_vencer'], true)) {
                        $vencidosMp[] = $this->enriquecerFilaVencimiento([
                            'ambito' => 'Vehículo MP',
                            'ref' => strtoupper((string)$v->placas),
                            'documento' => DocumentosVehiculoTable::tipoLabel($tipo),
                            'vence' => $porTipo[$tipo]->fecha_vencimiento
                                ? $porTipo[$tipo]->fecha_vencimiento->format('d/m/y')
                                : '—',
                            'estatus' => (string)$porTipo[$tipo]->estatus,
                            'url' => \Cake\Routing\Router::url([
                                'controller' => 'Vehiculos',
                                'action' => 'view',
                                $v->id,
                                '?' => ['tab' => 'mp'],
                            ]),
                        ], $porTipo[$tipo]->fecha_vencimiento ?? null);
                    }
                }
                $pctVeh = count($tiposMpVeh) > 0 ? (int)round(100 * $ok / count($tiposMpVeh)) : 0;
                $sumPctVehMp += $pctVeh;
                if ($pctVeh >= 100) {
                    $vehMpOk++;
                }
            }
            $pctVehMp = $vehMpTotal > 0 ? (int)round($sumPctVehMp / $vehMpTotal) : 0;

            $opsMp = $operadoresTable->find()
                ->contain(['DocumentosOperador'])
                ->where(['Operadores.empresa_id' => $eid, 'Operadores.estatus' => 'activo'])
                ->all();
            $opMpTotal = $opsMp->count();
            $opMpOk = 0;
            $sumPctOpMp = 0;
            foreach ($opsMp as $oper) {
                $porTipo = [];
                foreach ($oper->documentos_operador ?? [] as $doc) {
                    $porTipo[(string)$doc->tipo_documento] = $doc;
                }
                $ok = 0;
                foreach ($tiposMpOp as $tipo) {
                    if (isset($porTipo[$tipo]) && in_array((string)$porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                        $ok++;
                    }
                    if (isset($porTipo[$tipo]) && in_array((string)$porTipo[$tipo]->estatus, ['vencido', 'por_vencer'], true)) {
                        $vencidosMp[] = $this->enriquecerFilaVencimiento([
                            'ambito' => 'Operador MP',
                            'ref' => (string)($oper->nombre_completo ?? $oper->nombre ?? 'Operador'),
                            'documento' => DocumentosOperadorTable::tipoLabel($tipo),
                            'vence' => $porTipo[$tipo]->fecha_vencimiento
                                ? $porTipo[$tipo]->fecha_vencimiento->format('d/m/y')
                                : '—',
                            'estatus' => (string)$porTipo[$tipo]->estatus,
                            'url' => \Cake\Routing\Router::url([
                                'controller' => 'Operadores',
                                'action' => 'edit',
                                $oper->id,
                            ]),
                        ], $porTipo[$tipo]->fecha_vencimiento ?? null);
                    }
                }
                $pctOp = count($tiposMpOp) > 0 ? (int)round(100 * $ok / count($tiposMpOp)) : 0;
                $sumPctOpMp += $pctOp;
                if ($pctOp >= 100) {
                    $opMpOk++;
                }
            }
            $pctOpMp = $opMpTotal > 0 ? (int)round($sumPctOpMp / $opMpTotal) : 0;

            $adicQ = $docEmpresa->find()->where([
                'DocumentosEmpresa.empresa_id' => $eid,
                'DocumentosEmpresa.tipo_documento IN' => $tiposAdic !== [] ? $tiposAdic : ['__none__'],
            ]);
            $adicTotal = $tiposAdic !== [] ? (clone $adicQ)->count() : 0;
            $adicOk = $tiposAdic !== []
                ? (clone $adicQ)->where(['DocumentosEmpresa.estatus IN' => ['vigente', 'por_vencer']])->count()
                : 0;
            $pctAdic = $adicTotal > 0 ? (int)round(100 * $adicOk / $adicTotal) : 0;
            if ($tiposAdic !== []) {
                foreach ((clone $adicQ)->where(['DocumentosEmpresa.estatus IN' => ['vencido', 'por_vencer']])->limit(5)->all() as $doc) {
                    $vencidosMp[] = $this->enriquecerFilaVencimiento([
                        'ambito' => 'Doc. adicional',
                        'ref' => (string)$nombre,
                        'documento' => DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                        'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                        'estatus' => (string)$doc->estatus,
                        'url' => \Cake\Routing\Router::url(['controller' => 'DocumentosAdicionales', 'action' => 'index']),
                    ], $doc->fecha_vencimiento ?? null);
                }
            }

            $partsMp = [];
            if ($vehMpTotal > 0) {
                $partsMp[] = $pctVehMp;
            }
            if ($opMpTotal > 0) {
                $partsMp[] = $pctOpMp;
            }
            if ($adicTotal > 0) {
                $partsMp[] = $pctAdic;
            }
            $pctMp = $partsMp !== [] ? (int)round(array_sum($partsMp) / count($partsMp)) : 0;
            usort($vencidosMp, static fn(array $a, array $b): int => strcmp($a['vence'], $b['vence']));
            $vencidosMp = array_slice($vencidosMp, 0, 6);

            $contacto = $contactos[$eid] ?? ['telefono' => '', 'email' => ''];
            $resumen[] = [
                'id' => $eid,
                'nombre' => (string)$nombre,
                'rfc' => $rfcs[$eid] ?? '',
                'telefono' => (string)($contacto['telefono'] ?? ''),
                'email' => (string)($contacto['email'] ?? ''),
                'vehiculos' => $veh,
                'operadores' => $op,
                'alertas' => $alTotal,
                'alertasCriticas' => $alCrit,
                'cumplimiento' => $pct,
                'docsTotal' => $totalDocs,
                'docsCorrectos' => $correctos,
                'docsPorVencer' => $porVencer,
                'docsVencidos' => $vencidos,
                'docsFaltantes' => $faltantes,
                'vencidosLista' => $vencidosLista,
                'mp' => [
                    'pct' => $pctMp,
                    'vehiculos' => $vehMpTotal,
                    'vehiculosOk' => $vehMpOk,
                    'operadoresOk' => $opMpOk,
                    'operadores' => $opMpTotal,
                    'adicionalesOk' => $adicOk,
                    'adicionales' => $adicTotal,
                    'vencidos' => $vencidosMp,
                ],
                'esActiva' => $activaId !== null && $activaId === $eid,
            ];
        }

        usort($resumen, static function (array $a, array $b): int {
            // Primero las con más problemas, luego por nombre
            $scoreA = ((int)$a['docsVencidos'] * 10) + (int)$a['docsPorVencer'];
            $scoreB = ((int)$b['docsVencidos'] * 10) + (int)$b['docsPorVencer'];
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return strcasecmp($a['nombre'], $b['nombre']);
        });

        return $resumen;
    }

    /**
     * @param list<string> $estatus
     * @return list<array{ambito: string, ref: string, documento: string, vence: string, estatus: string, url: string|null}>
     */
    private function listarDocsEmpresaPorEstatus(
        $docEmpresa,
        $docVehiculo,
        $docOperador,
        int $empresaId,
        array $estatus,
        int $limit,
    ): array {
        $tiposAdic = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());
        $tiposMpVeh = DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos();
        $out = [];

        $empDocs = $docEmpresa->find()
            ->contain(['Empresas'])
            ->where([
                'DocumentosEmpresa.empresa_id' => $empresaId,
                'DocumentosEmpresa.estatus IN' => $estatus,
                'DocumentosEmpresa.tipo_documento NOT IN' => $tiposAdic !== [] ? $tiposAdic : ['__none__'],
            ])
            ->orderBy(['DocumentosEmpresa.fecha_vencimiento' => 'ASC'])
            ->limit($limit)
            ->all();
        foreach ($empDocs as $doc) {
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Empresa',
                'ref' => (string)($doc->empresa->nombre ?? 'Empresa'),
                'documento' => DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'url' => \Cake\Routing\Router::url(['controller' => 'Empresas', 'action' => 'index', '?' => ['empresa_id' => $empresaId]]),
            ], $doc->fecha_vencimiento ?? null);
        }

        $vehDocs = $docVehiculo->find()
            ->contain(['Vehiculos'])
            ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id' => $empresaId]))
            ->where([
                'DocumentosVehiculo.estatus IN' => $estatus,
                'DocumentosVehiculo.tipo_documento NOT IN' => $tiposMpVeh !== [] ? $tiposMpVeh : ['__none__'],
            ])
            ->orderBy(['DocumentosVehiculo.fecha_vencimiento' => 'ASC'])
            ->limit($limit)
            ->all();
        foreach ($vehDocs as $doc) {
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Vehículo',
                'ref' => strtoupper((string)($doc->vehiculo->placas ?? '—')),
                'documento' => DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'url' => \Cake\Routing\Router::url([
                    'controller' => 'Vehiculos',
                    'action' => 'view',
                    (int)$doc->vehiculo_id,
                ]),
            ], $doc->fecha_vencimiento ?? null);
        }

        $opDocs = $docOperador->find()
            ->contain(['Operadores'])
            ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id' => $empresaId]))
            ->where(['DocumentosOperador.estatus IN' => $estatus])
            ->orderBy(['DocumentosOperador.fecha_vencimiento' => 'ASC'])
            ->limit($limit)
            ->all();
        foreach ($opDocs as $doc) {
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Operador',
                'ref' => (string)($doc->operador->nombre_completo ?? $doc->operador->nombre ?? '—'),
                'documento' => DocumentosOperadorTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'url' => \Cake\Routing\Router::url([
                    'controller' => 'Operadores',
                    'action' => 'edit',
                    (int)$doc->operador_id,
                ]),
            ], $doc->fecha_vencimiento ?? null);
        }

        usort($out, static function (array $a, array $b): int {
            if ($a['estatus'] === $b['estatus']) {
                return strcmp($a['vence'], $b['vence']);
            }

            return $a['estatus'] === 'vencido' ? -1 : 1;
        });

        return array_slice($out, 0, $limit);
    }

    /**
     * @param list<int> $empresaIds
     * @return array{
     *   totalUnidadesMp: int,
     *   unidadesMpOk: int,
     *   pctVehiculosMp: int,
     *   totalOperadores: int,
     *   operadoresMpOk: int,
     *   pctOperadoresMp: int,
     *   totalDocsAdicionales: int,
     *   docsAdicionalesOk: int,
     *   pctAdicionales: int,
     *   pctGlobalMp: int,
     *   unidadesMpResumen: list<array{vehiculo: \App\Model\Entity\Vehiculo, pct: int, faltantes: int}>,
     *   vencimientosMp: list<array{ambito: string, ref: string, documento: string, vence: string, estatus: string, esMp: bool, url: string|null}>
     * }
     */
    private function calcularEstadisticasMp(
        $vehiculosTable,
        $operadoresTable,
        $docVehiculo,
        $docEmpresa,
        $docOperador,
        array $empresaIds,
    ): array {
        $tiposMpVeh = DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos();
        $tiposMpOp = DocumentosOperadorTable::tiposRequeridosMaterialesPeligrosos();
        $tiposAdic = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());
        $catalogoMp = DocumentosVehiculoTable::catalogoMaterialesPeligrosos();

        $vehMpQuery = $vehiculosTable->find()
            ->contain(['DocumentosVehiculo', 'Empresas'])
            ->where([
                'Vehiculos.estatus' => 'activo',
                'Vehiculos.tipo_carga' => 'material_peligroso',
            ]);
        if ($empresaIds !== []) {
            $vehMpQuery->where(['Vehiculos.empresa_id IN' => $empresaIds]);
        } else {
            $vehMpQuery->where(['0 = 1']);
        }
        $vehiculosMp = $vehMpQuery->all();

        $unidadesMpResumen = [];
        $unidadesMpOk = 0;
        $sumPctVeh = 0;
        foreach ($vehiculosMp as $v) {
            $porTipo = [];
            foreach ($v->documentos_vehiculo as $doc) {
                $porTipo[$doc->tipo_documento] = $doc;
            }
            $ok = 0;
            foreach ($tiposMpVeh as $tipo) {
                if (isset($porTipo[$tipo]) && in_array($porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $pct = count($tiposMpVeh) > 0 ? (int)round(100 * $ok / count($tiposMpVeh)) : 0;
            $sumPctVeh += $pct;
            if ($pct >= 100) {
                $unidadesMpOk++;
            }
            $unidadesMpResumen[] = [
                'vehiculo' => $v,
                'pct' => $pct,
                'faltantes' => count($tiposMpVeh) - $ok,
            ];
        }
        usort($unidadesMpResumen, static fn($a, $b) => $a['pct'] <=> $b['pct']);
        $totalUnidadesMp = count($unidadesMpResumen);
        $pctVehiculosMp = $totalUnidadesMp > 0 ? (int)round($sumPctVeh / $totalUnidadesMp) : 0;

        $opQuery = $operadoresTable->find()
            ->contain(['DocumentosOperador'])
            ->where(['Operadores.estatus' => 'activo']);
        if ($empresaIds !== []) {
            $opQuery->where(['Operadores.empresa_id IN' => $empresaIds]);
        } else {
            $opQuery->where(['0 = 1']);
        }
        $operadores = $opQuery->all();
        $operadoresMpOk = 0;
        $sumPctOp = 0;
        foreach ($operadores as $op) {
            $porTipo = [];
            foreach ($op->documentos_operador as $doc) {
                $porTipo[$doc->tipo_documento] = $doc;
            }
            $ok = 0;
            foreach ($tiposMpOp as $tipo) {
                if (isset($porTipo[$tipo]) && in_array($porTipo[$tipo]->estatus, ['vigente', 'por_vencer'], true)) {
                    $ok++;
                }
            }
            $pct = count($tiposMpOp) > 0 ? (int)round(100 * $ok / count($tiposMpOp)) : 0;
            $sumPctOp += $pct;
            if ($pct >= 100) {
                $operadoresMpOk++;
            }
        }
        $totalOperadoresMp = $operadores->count();
        $pctOperadoresMp = $totalOperadoresMp > 0 ? (int)round($sumPctOp / $totalOperadoresMp) : 0;

        $totalDocsAdicionales = 0;
        $docsAdicionalesOk = 0;
        $pctAdicionales = 0;
        if ($tiposAdic !== []) {
            $adicQ = $docEmpresa->find()->where(['DocumentosEmpresa.tipo_documento IN' => $tiposAdic]);
            if ($empresaIds !== []) {
                $adicQ->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds]);
            } else {
                $adicQ->where(['0 = 1']);
            }
            $totalDocsAdicionales = (clone $adicQ)->count();
            $docsAdicionalesOk = (clone $adicQ)->where(['DocumentosEmpresa.estatus IN' => ['vigente', 'por_vencer']])->count();
            $pctAdicionales = $totalDocsAdicionales > 0
                ? (int)round(100 * $docsAdicionalesOk / $totalDocsAdicionales)
                : 0;
        }

        $parts = [];
        if ($totalUnidadesMp > 0) {
            $parts[] = $pctVehiculosMp;
        }
        if ($totalOperadoresMp > 0) {
            $parts[] = $pctOperadoresMp;
        }
        if ($totalDocsAdicionales > 0) {
            $parts[] = $pctAdicionales;
        }
        $pctGlobalMp = $parts !== [] ? (int)round(array_sum($parts) / count($parts)) : 0;

        $vencimientosMp = [];
        foreach ($vehiculosMp as $v) {
            foreach ($v->documentos_vehiculo as $doc) {
                if (!in_array((string)$doc->tipo_documento, $tiposMpVeh, true)) {
                    continue;
                }
                if (!in_array($doc->estatus, ['vencido', 'por_vencer'], true)) {
                    continue;
                }
                $vencimientosMp[] = $this->enriquecerFilaVencimiento([
                    'ambito' => 'Vehículo MP',
                    'ref' => strtoupper((string)$v->placas),
                    'documento' => DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento),
                    'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/Y') : '—',
                    'estatus' => (string)$doc->estatus,
                    'esMp' => true,
                    'url' => \Cake\Routing\Router::url(['controller' => 'Vehiculos', 'action' => 'view', $v->id, '?' => ['tab' => 'mp']]),
                ], $doc->fecha_vencimiento ?? null);
            }
        }

        foreach ($operadores as $op) {
            foreach ($op->documentos_operador as $doc) {
                if (!in_array((string)$doc->tipo_documento, $tiposMpOp, true)) {
                    continue;
                }
                if (!in_array($doc->estatus, ['vencido', 'por_vencer'], true)) {
                    continue;
                }
                $vencimientosMp[] = $this->enriquecerFilaVencimiento([
                    'ambito' => 'Operador',
                    'ref' => $op->nombre_completo,
                    'documento' => DocumentosOperadorTable::tipoLabel((string)$doc->tipo_documento),
                    'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/Y') : '—',
                    'estatus' => (string)$doc->estatus,
                    'esMp' => true,
                    'url' => \Cake\Routing\Router::url(['controller' => 'Operadores', 'action' => 'edit', $op->id]),
                ], $doc->fecha_vencimiento ?? null);
            }
        }

        $adicVencer = $docEmpresa->find()
            ->contain(['Empresas'])
            ->where([
                'DocumentosEmpresa.tipo_documento IN' => $tiposAdic,
                'DocumentosEmpresa.estatus IN' => ['vencido', 'por_vencer'],
            ]);
        if ($empresaIds !== []) {
            $adicVencer->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds]);
        } else {
            $adicVencer->where(['0 = 1']);
        }
        foreach ($adicVencer->all() as $doc) {
            $empresasTable = $this->fetchTable('Empresas');
            $nombreCol = $empresasTable->razonSocialField();
            $refEmpresa = (string)($doc->empresa?->get($nombreCol) ?? $doc->empresa?->nombre ?? 'Empresa');
            $vencimientosMp[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Documento adicional',
                'ref' => $refEmpresa,
                'documento' => DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/Y') : '—',
                'estatus' => (string)$doc->estatus,
                'esMp' => true,
                'url' => \Cake\Routing\Router::url(['controller' => 'DocumentosAdicionales', 'action' => 'index']),
            ], $doc->fecha_vencimiento ?? null);
        }

        usort($vencimientosMp, static function (array $a, array $b): int {
            return strcmp($a['vence'], $b['vence']);
        });
        $vencimientosMp = array_slice($vencimientosMp, 0, 6);

        return [
            'totalUnidadesMp' => $totalUnidadesMp,
            'unidadesMpOk' => $unidadesMpOk,
            'pctVehiculosMp' => $pctVehiculosMp,
            'totalOperadores' => $totalOperadoresMp,
            'operadoresMpOk' => $operadoresMpOk,
            'pctOperadoresMp' => $pctOperadoresMp,
            'totalDocsAdicionales' => $totalDocsAdicionales,
            'docsAdicionalesOk' => $docsAdicionalesOk,
            'pctAdicionales' => $pctAdicionales,
            'pctGlobalMp' => $pctGlobalMp,
            'unidadesMpResumen' => array_slice($unidadesMpResumen, 0, 6),
            'vencimientosMp' => $vencimientosMp,
            'catalogoMp' => $catalogoMp,
            'tiposMpVehCount' => count($tiposMpVeh),
        ];
    }

    /**
     * @param list<int> $empresaIds
     * @return list<array<string, mixed>>
     */
    private function armarVencimientosProximos(
        $docEmpresa,
        $docVehiculo,
        $docOperador,
        array $empresaIds,
    ): array {
        $tiposMpVeh = DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos();
        $tiposAdic = array_keys(DocumentosEmpresaTable::tiposDocumentoAdicionales());
        $out = [];
        $contactos = $this->contactosEmpresas($empresaIds);

        $empresaQ = $docEmpresa->find()
            ->contain(['Empresas'])
            ->where([
                'DocumentosEmpresa.estatus IN' => ['vencido', 'por_vencer'],
                'DocumentosEmpresa.tipo_documento NOT IN' => $tiposAdic,
            ]);
        if ($empresaIds !== []) {
            $empresaQ->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds]);
        } else {
            $empresaQ->where(['0 = 1']);
        }
        foreach ($empresaQ->limit(3)->all() as $doc) {
            $eid = (int)$doc->empresa_id;
            $contacto = $contactos[$eid] ?? ['telefono' => '', 'email' => '', 'nombre' => ''];
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Empresa',
                'ref' => (string)($doc->empresa->nombre ?? 'Empresa'),
                'documento' => DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'empresa_id' => $eid,
                'empresa_nombre' => (string)($contacto['nombre'] !== '' ? $contacto['nombre'] : ($doc->empresa->nombre ?? 'Empresa')),
                'telefono' => (string)($contacto['telefono'] ?? ''),
                'email' => (string)($contacto['email'] ?? ''),
                'url' => \Cake\Routing\Router::url(['controller' => 'Empresas', 'action' => 'index', '?' => ['empresa_id' => $eid]]),
            ], $doc->fecha_vencimiento ?? null);
        }

        $vehQ = $docVehiculo->find()
            ->contain(['Vehiculos' => ['Empresas']])
            ->where([
                'DocumentosVehiculo.estatus IN' => ['vencido', 'por_vencer'],
                'DocumentosVehiculo.tipo_documento NOT IN' => $tiposMpVeh,
            ]);
        if ($empresaIds !== []) {
            $vehQ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]));
        } else {
            $vehQ->where(['0 = 1']);
        }
        foreach ($vehQ->limit(5)->all() as $doc) {
            $eid = (int)($doc->vehiculo->empresa_id ?? 0);
            $contacto = $contactos[$eid] ?? ['telefono' => '', 'email' => '', 'nombre' => ''];
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Vehículo',
                'ref' => strtoupper((string)($doc->vehiculo->placas ?? '—')),
                'documento' => DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'empresa_id' => $eid,
                'empresa_nombre' => (string)($contacto['nombre'] !== ''
                    ? $contacto['nombre']
                    : ($doc->vehiculo->empresa->nombre ?? 'Empresa')),
                'telefono' => (string)($contacto['telefono'] ?? ''),
                'email' => (string)($contacto['email'] ?? ''),
                'url' => \Cake\Routing\Router::url([
                    'controller' => 'Vehiculos',
                    'action' => 'view',
                    (int)$doc->vehiculo_id,
                ]),
            ], $doc->fecha_vencimiento ?? null);
        }

        $opQ = $docOperador->find()
            ->contain(['Operadores' => ['Empresas']])
            ->where(['DocumentosOperador.estatus IN' => ['vencido', 'por_vencer']]);
        if ($empresaIds !== []) {
            $opQ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]));
        } else {
            $opQ->where(['0 = 1']);
        }
        foreach ($opQ->limit(4)->all() as $doc) {
            $eid = (int)($doc->operador->empresa_id ?? 0);
            $contacto = $contactos[$eid] ?? ['telefono' => '', 'email' => '', 'nombre' => ''];
            $out[] = $this->enriquecerFilaVencimiento([
                'ambito' => 'Operador',
                'ref' => (string)($doc->operador->nombre_completo ?? $doc->operador->nombre ?? '—'),
                'documento' => DocumentosOperadorTable::tipoLabel((string)$doc->tipo_documento),
                'vence' => $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : '—',
                'estatus' => (string)$doc->estatus,
                'empresa_id' => $eid,
                'empresa_nombre' => (string)($contacto['nombre'] !== ''
                    ? $contacto['nombre']
                    : ($doc->operador->empresa->nombre ?? 'Empresa')),
                'telefono' => (string)($contacto['telefono'] ?? ''),
                'email' => (string)($contacto['email'] ?? ''),
                'url' => \Cake\Routing\Router::url([
                    'controller' => 'Operadores',
                    'action' => 'edit',
                    (int)$doc->operador_id,
                ]),
            ], $doc->fecha_vencimiento ?? null);
        }

        usort($out, static function (array $a, array $b): int {
            $da = $a['dias_restantes'] ?? PHP_INT_MAX;
            $db = $b['dias_restantes'] ?? PHP_INT_MAX;
            if ($a['estatus'] === 'vencido' && $b['estatus'] !== 'vencido') {
                return -1;
            }
            if ($b['estatus'] === 'vencido' && $a['estatus'] !== 'vencido') {
                return 1;
            }

            return $da <=> $db;
        });

        return array_slice($out, 0, 10);
    }

    /**
     * @param list<int> $empresaIds
     * @return array<int, array{telefono: string, email: string, nombre: string}>
     */
    private function contactosEmpresas(array $empresaIds): array
    {
        if ($empresaIds === []) {
            return [];
        }

        $empresasTable = $this->fetchTable('Empresas');
        $nombreCol = $empresasTable->razonSocialField();
        $out = [];
        try {
            foreach (
                $empresasTable->find()
                    ->select(['id', $nombreCol, 'telefono', 'email'])
                    ->where(['id IN' => $empresaIds])
                    ->all() as $e
            ) {
                $out[(int)$e->id] = [
                    'telefono' => trim((string)($e->telefono ?? '')),
                    'email' => trim((string)($e->email ?? '')),
                    'nombre' => trim((string)($e->get($nombreCol) ?? $e->nombre ?? '')),
                ];
            }
        } catch (\Throwable) {
            return [];
        }

        return $out;
    }
}
