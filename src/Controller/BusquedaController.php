<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;

class BusquedaController extends AppController
{
    private const LIMITE = 100;

    public function index(): void
    {
        $termino = trim((string)$this->request->getQuery('q', ''));
        $empresaIds = $this->empresaIdsConsulta();
        $porEmpresa = [];
        $total = 0;

        if ($termino !== '' && mb_strlen($termino) >= 2 && $empresaIds !== []) {
            $like = '%' . $termino . '%';
            $nombresEmpresa = $this->empresasAccesiblesSesion();
            $porEmpresa = $this->esqueletoPorEmpresa($empresaIds, $nombresEmpresa);

            $empresasTable = $this->fetchTable('Empresas');
            $col = $empresasTable->razonSocialField();

            $empresasMatch = $empresasTable->find()
                ->select(['id', $col, 'rfc', 'nombre'])
                ->where([
                    'Empresas.id IN' => $empresaIds,
                    'OR' => [
                        'Empresas.nombre LIKE' => $like,
                        'Empresas.rfc LIKE' => $like,
                        $col . ' LIKE' => $like,
                    ],
                ])
                ->limit(self::LIMITE)
                ->all();

            foreach ($empresasMatch as $e) {
                $eid = (int)$e->id;
                $this->asegurarGrupo($porEmpresa, $eid, $nombresEmpresa);
                $porEmpresa[$eid]['empresa'] = $e;
                $porEmpresa[$eid]['coincide_empresa'] = true;
                $total++;
            }

            $vehiculos = $this->fetchTable('Vehiculos')->find()
                ->select(['id', 'placas', 'numero_serie', 'marca', 'modelo', 'empresa_id'])
                ->where([
                    'Vehiculos.empresa_id IN' => $empresaIds,
                    'OR' => [
                        'Vehiculos.placas LIKE' => $like,
                        'Vehiculos.numero_serie LIKE' => $like,
                        'Vehiculos.marca LIKE' => $like,
                        'Vehiculos.modelo LIKE' => $like,
                    ],
                ])
                ->orderBy(['Vehiculos.empresa_id' => 'ASC', 'Vehiculos.placas' => 'ASC'])
                ->limit(self::LIMITE)
                ->all();

            foreach ($vehiculos as $v) {
                $eid = (int)$v->empresa_id;
                $this->asegurarGrupo($porEmpresa, $eid, $nombresEmpresa);
                $porEmpresa[$eid]['vehiculos'][] = $v;
                $total++;
            }

            $operadores = $this->fetchTable('Operadores')->find()
                ->select(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'num_licencia', 'empresa_id'])
                ->where([
                    'Operadores.empresa_id IN' => $empresaIds,
                    'OR' => [
                        'Operadores.nombre LIKE' => $like,
                        'Operadores.apellido_paterno LIKE' => $like,
                        'Operadores.apellido_materno LIKE' => $like,
                        'Operadores.num_licencia LIKE' => $like,
                        'Operadores.rfc LIKE' => $like,
                        "CONCAT(COALESCE(Operadores.nombre,''),' ',COALESCE(Operadores.apellido_paterno,''),' ',COALESCE(Operadores.apellido_materno,'')) LIKE" => $like,
                        "CONCAT('Operador ',COALESCE(Operadores.nombre,''),' ',COALESCE(Operadores.apellido_paterno,'')) LIKE" => $like,
                        "CONCAT('Licencia ',COALESCE(Operadores.num_licencia,'')) LIKE" => $like,
                    ],
                ])
                ->orderBy(['Operadores.empresa_id' => 'ASC', 'Operadores.nombre' => 'ASC'])
                ->limit(self::LIMITE)
                ->all();

            foreach ($operadores as $o) {
                $eid = (int)$o->empresa_id;
                $this->asegurarGrupo($porEmpresa, $eid, $nombresEmpresa);
                $porEmpresa[$eid]['operadores'][] = $o;
                $total++;
            }

            foreach ($this->buscarDocumentos($termino, $like, $empresaIds) as $doc) {
                $eid = (int)$doc['empresa_id'];
                if ($eid <= 0) {
                    continue;
                }
                $this->asegurarGrupo($porEmpresa, $eid, $nombresEmpresa);
                $porEmpresa[$eid]['documentos'][] = $doc;
                $total++;
            }

            // Solo empresas con al menos una coincidencia, ordenadas por nombre.
            $porEmpresa = array_filter(
                $porEmpresa,
                static fn(array $g): bool => $g['coincide_empresa']
                    || $g['vehiculos'] !== []
                    || $g['operadores'] !== []
                    || $g['documentos'] !== [],
            );
            uasort(
                $porEmpresa,
                static fn(array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']),
            );
        }

        $this->set(compact('termino', 'porEmpresa', 'total'));
    }

    /**
     * @param list<int> $empresaIds
     * @param array<int, string> $nombresEmpresa
     * @return array<int, array{
     *   empresa_id: int,
     *   nombre: string,
     *   coincide_empresa: bool,
     *   empresa: mixed,
     *   vehiculos: list,
     *   operadores: list,
     *   documentos: list
     * }>
     */
    private function esqueletoPorEmpresa(array $empresaIds, array $nombresEmpresa): array
    {
        $out = [];
        foreach ($empresaIds as $eid) {
            $this->asegurarGrupo($out, (int)$eid, $nombresEmpresa);
        }

        return $out;
    }

    /**
     * @param array<int, array{empresa_id: int, nombre: string, coincide_empresa: bool, empresa: mixed, vehiculos: list, operadores: list, documentos: list}> $porEmpresa
     * @param array<int, string> $nombresEmpresa
     */
    private function asegurarGrupo(array &$porEmpresa, int $empresaId, array $nombresEmpresa): void
    {
        if (isset($porEmpresa[$empresaId])) {
            return;
        }

        $porEmpresa[$empresaId] = [
            'empresa_id' => $empresaId,
            'nombre' => (string)($nombresEmpresa[$empresaId] ?? ('Empresa #' . $empresaId)),
            'coincide_empresa' => false,
            'empresa' => null,
            'vehiculos' => [],
            'operadores' => [],
            'documentos' => [],
        ];
    }

    /**
     * @param list<int> $empresaIds
     * @return list<array{
     *   ambito: string,
     *   id: int,
     *   empresa_id: int,
     *   tipo: string,
     *   tipo_label: string,
     *   nombre_archivo: string,
     *   ruta_archivo: string,
     *   estatus: string,
     *   contexto: string,
     *   edit_controller: string,
     *   edit_id: int
     * }>
     */
    private function buscarDocumentos(string $termino, string $like, array $empresaIds): array
    {
        $docs = [];
        $tiposEmp = $this->tiposCoincidentes(DocumentosEmpresaTable::tiposDocumento(), $termino);
        $tiposVeh = $this->tiposCoincidentes(DocumentosVehiculoTable::tiposDocumento(), $termino);
        $tiposOp = $this->tiposCoincidentes(DocumentosOperadorTable::tiposDocumento(), $termino);

        $orEmp = [
            'DocumentosEmpresa.nombre_archivo LIKE' => $like,
            'DocumentosEmpresa.tipo_documento LIKE' => $like,
        ];
        if ($tiposEmp !== []) {
            $orEmp['DocumentosEmpresa.tipo_documento IN'] = $tiposEmp;
        }

        $docsEmpresa = $this->fetchTable('DocumentosEmpresa')->find()
            ->contain(['Empresas'])
            ->where([
                'DocumentosEmpresa.empresa_id IN' => $empresaIds,
                'OR' => $orEmp,
            ])
            ->orderBy(['DocumentosEmpresa.empresa_id' => 'ASC', 'DocumentosEmpresa.updated_at' => 'DESC'])
            ->limit(self::LIMITE)
            ->all();

        foreach ($docsEmpresa as $doc) {
            $tipo = (string)$doc->tipo_documento;
            $esAdic = DocumentosEmpresaTable::esTipoAdicional($tipo);
            $docs[] = [
                'ambito' => $esAdic ? 'adicional' : 'empresa',
                'id' => (int)$doc->id,
                'empresa_id' => (int)$doc->empresa_id,
                'tipo' => $tipo,
                'tipo_label' => DocumentosEmpresaTable::tipoLabel($tipo),
                'nombre_archivo' => (string)($doc->nombre_archivo ?? ''),
                'ruta_archivo' => (string)($doc->ruta_archivo ?? ''),
                'estatus' => (string)($doc->estatus ?? ''),
                'contexto' => 'Documento de empresa',
                'edit_controller' => $esAdic ? 'DocumentosAdicionales' : 'DocumentosEmpresa',
                'edit_id' => (int)$doc->id,
            ];
        }

        $orVeh = [
            'DocumentosVehiculo.nombre_archivo LIKE' => $like,
            'DocumentosVehiculo.tipo_documento LIKE' => $like,
        ];
        if ($tiposVeh !== []) {
            $orVeh['DocumentosVehiculo.tipo_documento IN'] = $tiposVeh;
        }

        $docsVehiculo = $this->fetchTable('DocumentosVehiculo')->find()
            ->contain(['Vehiculos'])
            ->innerJoinWith('Vehiculos', fn($q) => $q->where(['Vehiculos.empresa_id IN' => $empresaIds]))
            ->where(['OR' => $orVeh])
            ->orderBy(['Vehiculos.empresa_id' => 'ASC', 'DocumentosVehiculo.updated_at' => 'DESC'])
            ->limit(self::LIMITE)
            ->all();

        foreach ($docsVehiculo as $doc) {
            $tipo = (string)$doc->tipo_documento;
            $placas = (string)($doc->vehiculo->placas ?? '');
            $serie = (string)($doc->vehiculo->numero_serie ?? '');
            $contexto = $placas !== '' ? $placas : ($serie !== '' ? 'VIN ' . $serie : 'Vehículo');
            $docs[] = [
                'ambito' => 'vehiculo',
                'id' => (int)$doc->id,
                'empresa_id' => (int)($doc->vehiculo->empresa_id ?? 0),
                'tipo' => $tipo,
                'tipo_label' => DocumentosVehiculoTable::tipoLabel($tipo),
                'nombre_archivo' => (string)($doc->nombre_archivo ?? ''),
                'ruta_archivo' => (string)($doc->ruta_archivo ?? ''),
                'estatus' => (string)($doc->estatus ?? ''),
                'contexto' => $contexto,
                'edit_controller' => 'DocumentosVehiculo',
                'edit_id' => (int)$doc->id,
            ];
        }

        $orOp = [
            'DocumentosOperador.nombre_archivo LIKE' => $like,
            'DocumentosOperador.tipo_documento LIKE' => $like,
        ];
        if ($tiposOp !== []) {
            $orOp['DocumentosOperador.tipo_documento IN'] = $tiposOp;
        }

        $docsOperador = $this->fetchTable('DocumentosOperador')->find()
            ->contain(['Operadores'])
            ->innerJoinWith('Operadores', fn($q) => $q->where(['Operadores.empresa_id IN' => $empresaIds]))
            ->where(['OR' => $orOp])
            ->orderBy(['Operadores.empresa_id' => 'ASC', 'DocumentosOperador.updated_at' => 'DESC'])
            ->limit(self::LIMITE)
            ->all();

        foreach ($docsOperador as $doc) {
            $tipo = (string)$doc->tipo_documento;
            $op = $doc->operador;
            $nombreOp = trim(
                (string)($op->nombre ?? '') . ' '
                . (string)($op->apellido_paterno ?? '') . ' '
                . (string)($op->apellido_materno ?? '')
            );
            $docs[] = [
                'ambito' => 'operador',
                'id' => (int)$doc->id,
                'empresa_id' => (int)($op->empresa_id ?? 0),
                'tipo' => $tipo,
                'tipo_label' => DocumentosOperadorTable::tipoLabel($tipo),
                'nombre_archivo' => (string)($doc->nombre_archivo ?? ''),
                'ruta_archivo' => (string)($doc->ruta_archivo ?? ''),
                'estatus' => (string)($doc->estatus ?? ''),
                'contexto' => $nombreOp !== '' ? $nombreOp : 'Operador',
                'edit_controller' => 'DocumentosOperador',
                'edit_id' => (int)$doc->id,
            ];
        }

        return $docs;
    }

    /**
     * @param array<string, string> $opciones
     * @return list<string>
     */
    private function tiposCoincidentes(array $opciones, string $termino): array
    {
        $needle = mb_strtolower($termino);
        $matches = [];
        foreach ($opciones as $codigo => $label) {
            if (
                str_contains(mb_strtolower((string)$codigo), $needle)
                || str_contains(mb_strtolower((string)$label), $needle)
            ) {
                $matches[] = (string)$codigo;
            }
        }

        return $matches;
    }
}
