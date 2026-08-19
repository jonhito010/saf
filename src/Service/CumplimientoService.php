<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Porcentaje de cumplimiento por empresa contra catálogo de documentos requeridos.
 *
 * Fórmula:
 *   ok / total_requeridos × 100
 * donde cada documento obligatorio cuenta (faltantes bajan el %).
 */
class CumplimientoService
{
    private const ESTATUS_OK = ['vigente', 'por_vencer'];

    /**
     * @return array{
     *   porcentaje: int,
     *   ok: int,
     *   total: int,
     *   faltantes: int,
     *   empresa_ok: int,
     *   empresa_total: int,
     *   vehiculos_ok: int,
     *   vehiculos_total: int,
     *   operadores_ok: int,
     *   operadores_total: int,
     *   mp_activo: bool,
     *   desglose: list<array{ambito: string, ref: string, tipo: string, ok: bool, estatus: string}>
     * }
     */
    public function resumenEmpresa(int $empresaId): array
    {
        $vacio = [
            'porcentaje' => 0,
            'ok' => 0,
            'total' => 0,
            'faltantes' => 0,
            'empresa_ok' => 0,
            'empresa_total' => 0,
            'vehiculos_ok' => 0,
            'vehiculos_total' => 0,
            'operadores_ok' => 0,
            'operadores_total' => 0,
            'mp_activo' => false,
            'desglose' => [],
        ];

        if ($empresaId <= 0) {
            return $vacio;
        }

        $locator = TableRegistry::getTableLocator();
        $empresas = $locator->get('Empresas');
        $vehiculos = $locator->get('Vehiculos');
        $operadores = $locator->get('Operadores');
        $docEmpresa = $locator->get('DocumentosEmpresa');
        $tipos = $locator->get('TiposDocumento');
        $catalogos = new CatalogosOperacionService();

        try {
            $empresa = $empresas->get($empresaId);
        } catch (\Throwable) {
            return $vacio;
        }

        $tiposMpCarga = $catalogos->codigosTipoCargaMp();
        $vehActivos = $vehiculos->find()
            ->contain(['DocumentosVehiculo'])
            ->where(['Vehiculos.empresa_id' => $empresaId, 'Vehiculos.estatus' => 'activo'])
            ->all();

        $tieneUnidadesMp = false;
        foreach ($vehActivos as $v) {
            if (in_array((string)$v->tipo_carga, $tiposMpCarga, true)) {
                $tieneUnidadesMp = true;
                break;
            }
        }

        $mpFlag = false;
        if ($empresas->getSchema()->hasColumn('transporte_materiales_peligrosos')) {
            $mpFlag = (int)($empresa->transporte_materiales_peligrosos ?? 0) === 1;
        }
        $mpActivo = $mpFlag || $tieneUnidadesMp;

        $reqEmpresa = $tipos->codigosRequeridosEmpresa();
        if ($mpActivo) {
            $reqEmpresa = array_values(array_unique(array_merge(
                $reqEmpresa,
                $tipos->codigosRequeridosMpEmpresa(),
            )));
        }

        $docsEmpPorTipo = [];
        if ($reqEmpresa !== []) {
            foreach ($docEmpresa->find()->where(['empresa_id' => $empresaId])->all() as $doc) {
                $codigo = (string)$doc->tipo_documento;
                // Conservar el mejor estatus si hay duplicados
                if (!isset($docsEmpPorTipo[$codigo]) || $this->estatusRank((string)$doc->estatus) > $this->estatusRank((string)$docsEmpPorTipo[$codigo]->estatus)) {
                    $docsEmpPorTipo[$codigo] = $doc;
                }
            }
        }

        $desglose = [];
        $ok = 0;
        $total = 0;
        $empOk = 0;
        $empTotal = 0;

        foreach ($reqEmpresa as $codigo) {
            $total++;
            $empTotal++;
            $doc = $docsEmpPorTipo[$codigo] ?? null;
            $estatus = $doc ? (string)$doc->estatus : 'faltante';
            $isOk = $doc !== null && in_array($estatus, self::ESTATUS_OK, true);
            if ($isOk) {
                $ok++;
                $empOk++;
            }
            $desglose[] = [
                'ambito' => 'empresa',
                'ref' => (string)($empresa->nombre ?? 'Empresa'),
                'tipo' => $codigo,
                'ok' => $isOk,
                'estatus' => $estatus,
            ];
        }

        $reqVeh = $tipos->codigosRequeridosNormativoVehiculo();
        $reqVehMp = $tipos->codigosRequeridosMpVehiculo();
        $vehOk = 0;
        $vehTotal = 0;

        foreach ($vehActivos as $v) {
            $porTipo = [];
            foreach ($v->documentos_vehiculo ?? [] as $doc) {
                $codigo = (string)$doc->tipo_documento;
                if (!isset($porTipo[$codigo]) || $this->estatusRank((string)$doc->estatus) > $this->estatusRank((string)$porTipo[$codigo]->estatus)) {
                    $porTipo[$codigo] = $doc;
                }
            }
            $requeridos = $reqVeh;
            if (in_array((string)$v->tipo_carga, $tiposMpCarga, true)) {
                $requeridos = array_values(array_unique(array_merge($requeridos, $reqVehMp)));
            }
            $ref = strtoupper((string)($v->placas ?? ('#' . $v->id)));
            foreach ($requeridos as $codigo) {
                $total++;
                $vehTotal++;
                $doc = $porTipo[$codigo] ?? null;
                $estatus = $doc ? (string)$doc->estatus : 'faltante';
                $isOk = $doc !== null && in_array($estatus, self::ESTATUS_OK, true);
                if ($isOk) {
                    $ok++;
                    $vehOk++;
                }
                $desglose[] = [
                    'ambito' => 'vehiculo',
                    'ref' => $ref,
                    'tipo' => $codigo,
                    'ok' => $isOk,
                    'estatus' => $estatus,
                ];
            }
        }

        $reqOp = $tipos->codigosRequeridosOperador();
        $reqOpMp = $tipos->codigosRequeridosMpOperador();
        $opOk = 0;
        $opTotal = 0;

        $ops = $operadores->find()
            ->contain(['DocumentosOperador'])
            ->where(['Operadores.empresa_id' => $empresaId, 'Operadores.estatus' => 'activo'])
            ->all();

        foreach ($ops as $op) {
            $porTipo = [];
            foreach ($op->documentos_operador ?? [] as $doc) {
                $codigo = (string)$doc->tipo_documento;
                if (!isset($porTipo[$codigo]) || $this->estatusRank((string)$doc->estatus) > $this->estatusRank((string)$porTipo[$codigo]->estatus)) {
                    $porTipo[$codigo] = $doc;
                }
            }
            $requeridos = $reqOp;
            if ($mpActivo) {
                $requeridos = array_values(array_unique(array_merge($requeridos, $reqOpMp)));
            }
            $ref = (string)($op->nombre_completo ?? $op->nombre ?? ('#' . $op->id));
            foreach ($requeridos as $codigo) {
                $total++;
                $opTotal++;
                $doc = $porTipo[$codigo] ?? null;
                $estatus = $doc ? (string)$doc->estatus : 'faltante';
                $isOk = $doc !== null && in_array($estatus, self::ESTATUS_OK, true);
                if ($isOk) {
                    $ok++;
                    $opOk++;
                }
                $desglose[] = [
                    'ambito' => 'operador',
                    'ref' => $ref,
                    'tipo' => $codigo,
                    'ok' => $isOk,
                    'estatus' => $estatus,
                ];
            }
        }

        $porcentaje = $total > 0 ? (int)round(($ok / $total) * 100) : 0;

        return [
            'porcentaje' => $porcentaje,
            'ok' => $ok,
            'total' => $total,
            'faltantes' => max(0, $total - $ok),
            'empresa_ok' => $empOk,
            'empresa_total' => $empTotal,
            'vehiculos_ok' => $vehOk,
            'vehiculos_total' => $vehTotal,
            'operadores_ok' => $opOk,
            'operadores_total' => $opTotal,
            'mp_activo' => $mpActivo,
            'desglose' => $desglose,
        ];
    }

    public function porcentajeEmpresa(int $empresaId): int
    {
        return $this->resumenEmpresa($empresaId)['porcentaje'];
    }

    /**
     * Promedio ponderado por requisitos de varias empresas.
     *
     * @param list<int> $empresaIds
     */
    public function porcentajeConsolidado(array $empresaIds): int
    {
        $ok = 0;
        $total = 0;
        foreach ($empresaIds as $id) {
            $r = $this->resumenEmpresa((int)$id);
            $ok += $r['ok'];
            $total += $r['total'];
        }

        return $total > 0 ? (int)round(($ok / $total) * 100) : 0;
    }

    private function estatusRank(string $estatus): int
    {
        return match ($estatus) {
            'vigente' => 3,
            'por_vencer' => 2,
            'vencido' => 1,
            default => 0,
        };
    }
}
