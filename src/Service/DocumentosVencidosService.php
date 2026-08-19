<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Consulta documentos vencidos o por vencer para alertas al acceder al sistema.
 */
class DocumentosVencidosService
{
    use LocatorAwareTrait;

    /** @var list<string> */
    private const ESTATUS_ALERTA = ['vencido', 'por_vencer'];

    /**
     * @return list<array{
     *   modulo: string,
     *   modulo_label: string,
     *   tipo_label: string,
     *   referencia: string,
     *   estatus: string,
     *   estatus_label: string,
     *   fecha_vencimiento: string|null,
     *   url: array<string, mixed>
     * }>
     */
    /**
     * @param list<int>|int|null $empresaIds Una empresa, varias, o null (sin filtro de empresa).
     */
    public function listarParaModal(array|int|null $empresaIds = null): array
    {
        if (is_int($empresaIds)) {
            $empresaIds = $empresaIds > 0 ? [$empresaIds] : [];
        }
        $empresaIds = array_values(array_filter(
            array_map('intval', $empresaIds ?? []),
            static fn(int $id): bool => $id > 0,
        ));

        // Sin empresas accesibles: no mostrar documentos de otros tenants.
        if ($empresaIds === []) {
            return [];
        }

        $items = [];
        $hoy = Date::today();

        $docEmpresa = $this->fetchTable('DocumentosEmpresa');
        $qEmp = $docEmpresa->find()
            ->contain(['Empresas'])
            ->where($this->condicionVencimiento('DocumentosEmpresa', $hoy))
            ->orderBy(['DocumentosEmpresa.estatus' => 'ASC', 'DocumentosEmpresa.fecha_vencimiento' => 'ASC']);
        $qEmp->where(['DocumentosEmpresa.empresa_id IN' => $empresaIds]);
        foreach ($qEmp->all() as $doc) {
            $empresa = $doc->empresa ?? null;
            $items[] = $this->filaItem(
                'empresa',
                'Empresa',
                DocumentosEmpresaTable::tipoLabel((string)$doc->tipo_documento),
                (string)($empresa->nombre ?? 'Empresa'),
                (string)$doc->estatus,
                $doc->fecha_vencimiento,
                ['controller' => 'Empresas', 'action' => 'index'],
            );
        }

        $docVehiculo = $this->fetchTable('DocumentosVehiculo');
        $qVeh = $docVehiculo->find()
            ->contain(['Vehiculos'])
            ->where($this->condicionVencimiento('DocumentosVehiculo', $hoy))
            ->orderBy(['DocumentosVehiculo.estatus' => 'ASC', 'DocumentosVehiculo.fecha_vencimiento' => 'ASC']);
        $qVeh->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]));
        foreach ($qVeh->all() as $doc) {
            $veh = $doc->vehiculo ?? null;
            $placas = (string)($veh->placas ?? '');
            $items[] = $this->filaItem(
                'vehiculo',
                'Vehículo',
                DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento),
                $placas !== '' ? $placas : ('Vehículo #' . ($veh->id ?? '')),
                (string)$doc->estatus,
                $doc->fecha_vencimiento,
                $veh && $veh->id
                    ? ['controller' => 'Vehiculos', 'action' => 'view', (int)$veh->id]
                    : ['controller' => 'Vehiculos', 'action' => 'index'],
            );
        }

        $docOperador = $this->fetchTable('DocumentosOperador');
        $qOp = $docOperador->find()
            ->contain(['Operadores'])
            ->where($this->condicionVencimiento('DocumentosOperador', $hoy))
            ->orderBy(['DocumentosOperador.estatus' => 'ASC', 'DocumentosOperador.fecha_vencimiento' => 'ASC']);
        $qOp->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]));
        foreach ($qOp->all() as $doc) {
            $op = $doc->operador ?? null;
            $items[] = $this->filaItem(
                'operador',
                'Operador',
                DocumentosOperadorTable::tipoLabel((string)$doc->tipo_documento),
                (string)($op->nombre_completo ?? ('Operador #' . ($op->id ?? ''))),
                (string)$doc->estatus,
                $doc->fecha_vencimiento,
                ['controller' => 'Operadores', 'action' => 'index'],
            );
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['fecha_vencimiento'] === null && $b['fecha_vencimiento'] === null) {
                return strcmp($a['referencia'], $b['referencia']);
            }
            if ($a['fecha_vencimiento'] === null) {
                return 1;
            }
            if ($b['fecha_vencimiento'] === null) {
                return -1;
            }

            return strcmp($a['fecha_vencimiento'], $b['fecha_vencimiento']);
        });

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function condicionVencimiento(string $alias, Date $hoy): array
    {
        return [
            'OR' => [
                "{$alias}.estatus IN" => self::ESTATUS_ALERTA,
                [
                    "{$alias}.fecha_vencimiento <" => $hoy,
                    "{$alias}.fecha_vencimiento IS NOT" => null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $url
     * @return array<string, mixed>
     */
    private function filaItem(
        string $modulo,
        string $moduloLabel,
        string $tipoLabel,
        string $referencia,
        string $estatus,
        mixed $fecha,
        array $url,
    ): array {
        return [
            'modulo' => $modulo,
            'modulo_label' => $moduloLabel,
            'tipo_label' => $tipoLabel,
            'referencia' => $referencia,
            'estatus' => $estatus,
            'estatus_label' => $estatus === 'vencido' ? 'Vencido' : 'Por vencer',
            'fecha_vencimiento' => $this->formatFecha($fecha),
            'url' => $url,
        ];
    }

    private function formatFecha(mixed $fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }
        if ($fecha instanceof Date) {
            return $fecha->format('d/m/Y');
        }
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('d/m/Y');
        }

        return (string)$fecha;
    }
}
