<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Service\AlertasReglasService;
use App\Service\AuditoriaService;
use App\Service\ExportacionService;
use App\Utility\DocumentoUtil;
use Cake\Http\Response;
use Cake\I18n\Date;

/**
 * Reportes unificados de documentos (empresa, vehículo, operador).
 */
class ReportesController extends AppController
{
    /** @var list<string> */
    private const ESTATUS_FILTRO = ['activo', 'vigente', 'por_vencer', 'vencido'];

    public function index(): void
    {
        $filtros = $this->filtrosRequest();
        $filas = $this->consultarDocumentos($filtros, $this->empresaIdsAlcance());

        $this->set([
            'filas' => $filas,
            'filtros' => $filtros,
            'operadoresList' => $this->operadoresListAlcance(false),
            'tiposDocumento' => $this->opcionesTipoDocumento(),
        ] + $this->datosFiltroEmpresaVista());
        $this->set('filtroPreserve', $this->filtroPreserve($filtros));
    }

    public function exportarCsv(): Response
    {
        return $this->exportar('csv', false);
    }

    public function exportarXlsx(): Response
    {
        return $this->exportar('xlsx', false);
    }

    public function exportarCompleto(): Response
    {
        return $this->exportar('csv', true);
    }

    private function exportar(string $formato, bool $completo): Response
    {
        $filtros = $this->filtrosRequest();
        $empresaIds = $completo ? $this->empresaIdsConsulta() : $this->empresaIdsAlcance();
        $filas = $this->consultarDocumentos($filtros, $empresaIds);

        $headers = ['Empresa', 'Sujeto', 'Ámbito', 'Tipo documento', 'Vencimiento', 'Días restantes', 'Estatus'];
        $rows = [];
        foreach ($filas as $f) {
            $rows[] = [
                $f['empresa'],
                $f['sujeto'],
                $f['ambito'],
                $f['tipo_label'],
                $f['fecha_vencimiento'] ?? '',
                $f['dias_restantes'] ?? '',
                $f['estatus'],
            ];
        }

        $suffix = date('Y-m-d_His');
        $filename = $completo ? "reporte_completo_{$suffix}" : "reporte_{$suffix}";
        $export = new ExportacionService();

        if (class_exists(AuditoriaService::class)) {
            (new AuditoriaService())->registrar(
                'export',
                'reportes',
                'Exportación ' . ($completo ? 'completa' : $formato) . ' (' . count($filas) . ' filas)',
                ['formato' => $formato, 'completo' => $completo, 'filtros' => $filtros],
                $this->usuarioIdSesion(),
                $this->empresaIdSesion(),
            );
        }

        if ($formato === 'xlsx') {
            return $export->exportarXlsx($headers, $rows, $filename . '.xlsx');
        }

        return $export->exportarCsv($headers, $rows, $filename . '.csv');
    }

    /**
     * @return array{
     *   empresa_id: int|null,
     *   operador_id: int|null,
     *   tipo_documento: string,
     *   estatus: string,
     *   fecha_desde: string,
     *   fecha_hasta: string,
     *   q: string
     * }
     */
    private function filtrosRequest(): array
    {
        $estatus = trim((string)$this->request->getQuery('estatus', ''));
        if (!in_array($estatus, self::ESTATUS_FILTRO, true)) {
            $estatus = '';
        }

        $operadorRaw = $this->request->getQuery('operador_id');
        $operadorId = ($operadorRaw !== null && $operadorRaw !== '') ? (int)$operadorRaw : null;

        return [
            'empresa_id' => $this->filtroEmpresaIdRequest(),
            'operador_id' => ($operadorId !== null && $operadorId > 0) ? $operadorId : null,
            'tipo_documento' => trim((string)$this->request->getQuery('tipo_documento', '')),
            'estatus' => $estatus,
            'fecha_desde' => trim((string)$this->request->getQuery('fecha_desde', '')),
            'fecha_hasta' => trim((string)$this->request->getQuery('fecha_hasta', '')),
            'q' => trim((string)$this->request->getQuery('q', '')),
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     * @param list<int> $empresaIds
     * @return list<array{
     *   empresa: string,
     *   sujeto: string,
     *   ambito: string,
     *   tipo: string,
     *   tipo_label: string,
     *   fecha_vencimiento: string|null,
     *   dias_restantes: int|null,
     *   estatus: string,
     *   empresa_id: int
     * }>
     */
    private function consultarDocumentos(array $filtros, array $empresaIds): array
    {
        if ($empresaIds === []) {
            return [];
        }

        $nombresEmpresa = $this->empresasAccesiblesSesion();
        $filas = [];

        $filas = array_merge($filas, $this->documentosEmpresa($empresaIds, $nombresEmpresa));
        $filas = array_merge($filas, $this->documentosVehiculo($empresaIds, $nombresEmpresa));
        $filas = array_merge($filas, $this->documentosOperador($empresaIds, $nombresEmpresa));

        return $this->aplicarFiltrosFilas($filas, $filtros);
    }

    /**
     * @param list<int> $empresaIds
     * @param array<int, string> $nombresEmpresa
     * @return list<array<string, mixed>>
     */
    private function documentosEmpresa(array $empresaIds, array $nombresEmpresa): array
    {
        $rows = $this->fetchTable('DocumentosEmpresa')->find()
            ->contain(['Empresas'])
            ->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds])
            ->orderBy(['DocumentosEmpresa.empresa_id' => 'ASC', 'DocumentosEmpresa.tipo_documento' => 'ASC'])
            ->all();

        $out = [];
        foreach ($rows as $doc) {
            $tipo = (string)$doc->tipo_documento;
            $empresaNombre = $nombresEmpresa[(int)$doc->empresa_id]
                ?? $doc->empresa?->nombre
                ?? $doc->empresa?->razon_social
                ?? 'Empresa';
            $out[] = $this->filaDocumento(
                (int)$doc->empresa_id,
                (string)$empresaNombre,
                'Empresa',
                'empresa',
                $tipo,
                DocumentosEmpresaTable::tipoLabel($tipo),
                $doc->fecha_vencimiento ?? null,
                (string)($doc->estatus ?? DocumentoUtil::estatusDesdeFecha($doc->fecha_vencimiento ?? null)),
                null,
                true,
            );
        }

        return $out;
    }

    /**
     * @param list<int> $empresaIds
     * @param array<int, string> $nombresEmpresa
     * @return list<array<string, mixed>>
     */
    private function documentosVehiculo(array $empresaIds, array $nombresEmpresa): array
    {
        $rows = $this->fetchTable('DocumentosVehiculo')->find()
            ->contain(['Vehiculos'])
            ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]))
            ->orderBy(['Vehiculos.empresa_id' => 'ASC', 'DocumentosVehiculo.tipo_documento' => 'ASC'])
            ->all();

        $out = [];
        foreach ($rows as $doc) {
            $veh = $doc->vehiculo ?? null;
            if ($veh === null) {
                continue;
            }
            $eid = (int)($veh->empresa_id ?? 0);
            $tipo = (string)$doc->tipo_documento;
            $placas = (string)($veh->placas ?? '');
            $sujeto = $placas !== '' ? $placas : ('Vehículo #' . (int)($veh->id ?? 0));
            $out[] = $this->filaDocumento(
                $eid,
                (string)($nombresEmpresa[$eid] ?? 'Empresa'),
                $sujeto,
                'vehiculo',
                $tipo,
                DocumentosVehiculoTable::tipoLabel($tipo),
                $doc->fecha_vencimiento ?? null,
                (string)($doc->estatus ?? DocumentoUtil::estatusDesdeFecha($doc->fecha_vencimiento ?? null)),
                (string)($veh->estatus ?? ''),
                true,
            );
        }

        return $out;
    }

    /**
     * @param list<int> $empresaIds
     * @param array<int, string> $nombresEmpresa
     * @return list<array<string, mixed>>
     */
    private function documentosOperador(array $empresaIds, array $nombresEmpresa): array
    {
        $rows = $this->fetchTable('DocumentosOperador')->find()
            ->contain(['Operadores'])
            ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]))
            ->orderBy(['Operadores.empresa_id' => 'ASC', 'DocumentosOperador.tipo_documento' => 'ASC'])
            ->all();

        $out = [];
        foreach ($rows as $doc) {
            $op = $doc->operador ?? null;
            if ($op === null) {
                continue;
            }
            $eid = (int)($op->empresa_id ?? 0);
            $tipo = (string)$doc->tipo_documento;
            $nombreOp = trim(
                (string)($op->nombre ?? '') . ' '
                . (string)($op->apellido_paterno ?? '') . ' '
                . (string)($op->apellido_materno ?? '')
            );
            if ($nombreOp === '' && !empty($op->nombre_completo)) {
                $nombreOp = trim((string)$op->nombre_completo);
            }
            $opId = (int)($op->id ?? $doc->operador_id ?? 0);
            $sujeto = $nombreOp !== '' ? $nombreOp : ('Operador #' . $opId);
            $out[] = $this->filaDocumento(
                $eid,
                (string)($nombresEmpresa[$eid] ?? 'Empresa'),
                $sujeto,
                'operador',
                $tipo,
                DocumentosOperadorTable::tipoLabel($tipo),
                $doc->fecha_vencimiento ?? null,
                (string)($doc->estatus ?? DocumentoUtil::estatusDesdeFecha($doc->fecha_vencimiento ?? null)),
                (string)($op->estatus ?? ''),
                true,
                $opId > 0 ? $opId : null,
            );
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function filaDocumento(
        int $empresaId,
        string $empresa,
        string $sujeto,
        string $ambito,
        string $tipo,
        string $tipoLabel,
        mixed $fechaVenc,
        string $estatus,
        ?string $estatusEntidad,
        bool $entidadActivaDefault,
        ?int $operadorId = null,
    ): array {
        $dias = AlertasReglasService::diasRestantes($fechaVenc);
        $fechaStr = null;
        if ($fechaVenc instanceof \DateTimeInterface) {
            $fechaStr = $fechaVenc->format('Y-m-d');
        } elseif ($fechaVenc !== null && $fechaVenc !== '') {
            $fechaStr = (string)$fechaVenc;
        }

        return [
            'empresa_id' => $empresaId,
            'empresa' => $empresa,
            'sujeto' => $sujeto,
            'ambito' => ucfirst($ambito),
            'tipo' => $tipo,
            'tipo_label' => $tipoLabel,
            'fecha_vencimiento' => $fechaStr,
            'dias_restantes' => $dias,
            'estatus' => $estatus,
            'estatus_entidad' => $estatusEntidad ?? ($entidadActivaDefault ? 'activo' : ''),
            'operador_id' => $operadorId,
        ];
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @param array<string, mixed> $filtros
     * @return list<array<string, mixed>>
     */
    private function aplicarFiltrosFilas(array $filas, array $filtros): array
    {
        $out = [];
        $q = mb_strtolower((string)($filtros['q'] ?? ''));
        $tipoFiltro = (string)($filtros['tipo_documento'] ?? '');
        $estatusFiltro = (string)($filtros['estatus'] ?? '');
        $operadorId = $filtros['operador_id'] ?? null;
        $fechaDesde = $this->parseFecha((string)($filtros['fecha_desde'] ?? ''));
        $fechaHasta = $this->parseFecha((string)($filtros['fecha_hasta'] ?? ''));

        foreach ($filas as $f) {
            if ($operadorId !== null) {
                if (($f['ambito'] ?? '') !== 'Operador' || (int)($f['operador_id'] ?? 0) !== $operadorId) {
                    continue;
                }
            }

            if ($tipoFiltro !== '' && ($f['tipo'] ?? '') !== $tipoFiltro) {
                continue;
            }

            if ($estatusFiltro !== '') {
                if ($estatusFiltro === 'activo') {
                    if (($f['estatus_entidad'] ?? '') !== 'activo' && ($f['ambito'] ?? '') !== 'Empresa') {
                        continue;
                    }
                } elseif (($f['estatus'] ?? '') !== $estatusFiltro) {
                    continue;
                }
            }

            if ($fechaDesde !== null || $fechaHasta !== null) {
                $fv = $f['fecha_vencimiento'] ?? null;
                if ($fv === null || $fv === '') {
                    continue;
                }
                try {
                    $fd = new Date((string)$fv);
                } catch (\Throwable) {
                    continue;
                }
                if ($fechaDesde !== null && $fd < $fechaDesde) {
                    continue;
                }
                if ($fechaHasta !== null && $fd > $fechaHasta) {
                    continue;
                }
            }

            if ($q !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string)($f['empresa'] ?? ''),
                    (string)($f['sujeto'] ?? ''),
                    (string)($f['tipo_label'] ?? ''),
                    (string)($f['tipo'] ?? ''),
                    (string)($f['ambito'] ?? ''),
                ]));
                if (!str_contains($haystack, $q)) {
                    continue;
                }
            }

            $out[] = $f;
        }

        usort($out, static function (array $a, array $b): int {
            $ea = (string)($a['empresa'] ?? '');
            $eb = (string)($b['empresa'] ?? '');
            $cmp = strcasecmp($ea, $eb);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string)($a['tipo_label'] ?? ''), (string)($b['tipo_label'] ?? ''));
        });

        return $out;
    }

    private function parseFecha(string $raw): ?Date
    {
        if ($raw === '') {
            return null;
        }
        try {
            return new Date($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, string> */
    private function opcionesTipoDocumento(): array
    {
        $opts = DocumentosEmpresaTable::tiposDocumento();
        foreach (DocumentosVehiculoTable::tiposDocumento() as $k => $v) {
            $opts[$k] = $v;
        }
        foreach (DocumentosOperadorTable::tiposDocumento() as $k => $v) {
            $opts[$k] = $v;
        }
        asort($opts);

        return $opts;
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, string>
     */
    private function filtroPreserve(array $filtros): array
    {
        $preserve = [];
        foreach (['operador_id', 'tipo_documento', 'estatus', 'fecha_desde', 'fecha_hasta', 'q'] as $key) {
            $val = $filtros[$key] ?? '';
            if ($val !== null && $val !== '') {
                $preserve[$key] = (string)$val;
            }
        }

        return $preserve;
    }
}
