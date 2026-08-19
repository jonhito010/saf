<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\UsuarioEmpresaService;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * Auditoría simple: actividad reciente en lenguaje claro.
 */
class AuditoriaController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $rol = $this->rolIdSesion();
        if ($rol !== UsuarioEmpresaService::ROL_SUPERADMIN && $rol !== 2) {
            throw new ForbiddenException('No tienes permiso para ver la auditoría.');
        }
    }

    public function index(): void
    {
        $filtros = $this->filtrosSimples();
        $eventos = $this->consultarEventosUnificados($filtros);
        $empresasFiltro = $this->empresasAccesiblesSesion();
        $periodos = [
            'hoy' => 'Hoy',
            '7' => 'Últimos 7 días',
            '30' => 'Últimos 30 días',
            'todo' => 'Todo',
        ];
        $tipos = [
            '' => 'Toda la actividad',
            'acceso' => 'Inicios de sesión',
            'operador' => 'Operadores',
            'cambio' => 'Cambios de datos',
            'sistema' => 'Otras acciones',
        ];

        $this->set(compact('eventos', 'filtros', 'empresasFiltro', 'periodos', 'tipos'));
    }

    /**
     * Compatibilidad con menú/enlace antiguo → misma vista simple.
     */
    public function cambios(): Response
    {
        return $this->redirect([
            'action' => 'index',
            '?' => array_filter([
                'tipo' => 'cambio',
                'periodo' => $this->request->getQuery('periodo'),
                'q' => $this->request->getQuery('q'),
                'empresa_id' => $this->request->getQuery('empresa_id'),
            ], static fn($v) => $v !== null && $v !== ''),
        ]);
    }

    /**
     * @return array{periodo: string, tipo: string, empresa_id: int|null, q: string}
     */
    private function filtrosSimples(): array
    {
        $periodo = (string)$this->request->getQuery('periodo', '7');
        if (!in_array($periodo, ['hoy', '7', '30', 'todo'], true)) {
            $periodo = '7';
        }

        $tipo = (string)$this->request->getQuery('tipo', '');
        if (!in_array($tipo, ['', 'acceso', 'operador', 'cambio', 'sistema'], true)) {
            $tipo = '';
        }

        $empresaRaw = $this->request->getQuery('empresa_id');
        $empresaId = null;
        if ($empresaRaw !== null && $empresaRaw !== '' && (string)$empresaRaw !== 'todas') {
            $eid = (int)$empresaRaw;
            if ($eid > 0 && ($this->esSuperadmin() || $this->entidadEnAlcanceEmpresa($eid))) {
                $empresaId = $eid;
            }
        }

        return [
            'periodo' => $periodo,
            'tipo' => $tipo,
            'empresa_id' => $empresaId,
            'q' => trim((string)$this->request->getQuery('q', '')),
        ];
    }

    /**
     * @param array{periodo: string, tipo: string, empresa_id: int|null, q: string} $filtros
     * @return list<array<string, mixed>>
     */
    private function consultarEventosUnificados(array $filtros): array
    {
        $logs = $this->consultarSistemaLogs($filtros);
        $cambios = $this->consultarAuditoriaCambios($filtros);

        $eventos = [];
        foreach ($logs as $log) {
            $categoria = $this->categoriaDesdeLog($log);
            if (!$this->pasaFiltroTipo($filtros['tipo'], $categoria)) {
                continue;
            }

            $eventos[] = [
                'cuando' => (string)($log['created_at'] ?? ''),
                'categoria' => $categoria,
                'titulo' => $this->tituloDesdeLog($log),
                'detalle' => trim((string)($log['descripcion'] ?? '')),
                'quien' => $this->nombreUsuario($log),
                'empresa' => $this->nombreEmpresa((int)($log['empresa_id'] ?? 0)),
                'origen' => 'log',
            ];
        }

        if ($filtros['tipo'] === '' || $filtros['tipo'] === 'cambio' || $filtros['tipo'] === 'operador') {
            foreach ($cambios as $c) {
                $entidad = strtolower((string)($c['entidad_tipo'] ?? ''));
                if ($filtros['tipo'] === 'operador' && $entidad !== 'operador') {
                    continue;
                }

                $eventos[] = [
                    'cuando' => (string)($c['created_at'] ?? ''),
                    'categoria' => 'cambio',
                    'titulo' => $this->tituloDesdeCambio($c),
                    'detalle' => $this->detalleDesdeCambio($c),
                    'quien' => $this->nombreUsuario($c),
                    'empresa' => $this->nombreEmpresa((int)($c['empresa_id'] ?? 0)),
                    'origen' => 'cambio',
                ];
            }
        }

        usort(
            $eventos,
            static fn(array $a, array $b): int => strcmp((string)$b['cuando'], (string)$a['cuando']),
        );

        return array_slice($eventos, 0, 300);
    }

    /**
     * @param array{periodo: string, tipo: string, empresa_id: int|null, q: string} $filtros
     * @return list<array<string, mixed>>
     */
    private function consultarSistemaLogs(array $filtros): array
    {
        $conn = ConnectionManager::get('default');
        $where = ['1=1'];
        $params = [];

        $this->aplicarAlcanceEmpresaSql($where, $params, 'sl.empresa_id');
        $this->aplicarPeriodoSql($where, $params, 'sl.created_at', $filtros['periodo']);

        if ($filtros['empresa_id'] !== null) {
            $where[] = 'sl.empresa_id = :empresa_id_f';
            $params['empresa_id_f'] = $filtros['empresa_id'];
        }
        if ($filtros['q'] !== '') {
            $where[] = '(sl.descripcion LIKE :q OR sl.accion LIKE :q OR sl.modulo LIKE :q OR u.nombre LIKE :q OR u.email LIKE :q)';
            $params['q'] = '%' . $filtros['q'] . '%';
        }

        $sql = 'SELECT sl.id, sl.usuario_id, sl.empresa_id, sl.accion, sl.modulo, sl.descripcion,
                       sl.nivel, sl.created_at,
                       u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM sistema_logs sl
                LEFT JOIN usuarios u ON u.id = sl.usuario_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY sl.created_at DESC, sl.id DESC
                LIMIT 300';

        try {
            return $conn->execute($sql, $params)->fetchAll('assoc');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array{periodo: string, tipo: string, empresa_id: int|null, q: string} $filtros
     * @return list<array<string, mixed>>
     */
    private function consultarAuditoriaCambios(array $filtros): array
    {
        $conn = ConnectionManager::get('default');
        $where = ['1=1'];
        $params = [];

        $this->aplicarAlcanceEmpresaSql($where, $params, 'ac.empresa_id');
        $this->aplicarPeriodoSql($where, $params, 'ac.created_at', $filtros['periodo']);

        if ($filtros['empresa_id'] !== null) {
            $where[] = 'ac.empresa_id = :empresa_id_f';
            $params['empresa_id_f'] = $filtros['empresa_id'];
        }
        if ($filtros['q'] !== '') {
            $where[] = '(ac.campo LIKE :q OR ac.valor_anterior LIKE :q OR ac.valor_nuevo LIKE :q
                        OR ac.entidad_tipo LIKE :q OR u.nombre LIKE :q OR u.email LIKE :q)';
            $params['q'] = '%' . $filtros['q'] . '%';
        }

        $sql = 'SELECT ac.id, ac.empresa_id, ac.usuario_id, ac.entidad_tipo, ac.entidad_id,
                       ac.campo, ac.valor_anterior, ac.valor_nuevo, ac.created_at,
                       u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM auditoria_cambios ac
                LEFT JOIN usuarios u ON u.id = ac.usuario_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ac.created_at DESC, ac.id DESC
                LIMIT 300';

        try {
            return $conn->execute($sql, $params)->fetchAll('assoc');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<string> $where
     * @param array<string, mixed> $params
     */
    private function aplicarPeriodoSql(array &$where, array &$params, string $campo, string $periodo): void
    {
        if ($periodo === 'todo') {
            return;
        }
        if ($periodo === 'hoy') {
            $where[] = $campo . ' >= :periodo_desde';
            $params['periodo_desde'] = date('Y-m-d 00:00:00');

            return;
        }
        $dias = $periodo === '30' ? 30 : 7;
        $where[] = $campo . ' >= :periodo_desde';
        $params['periodo_desde'] = date('Y-m-d 00:00:00', strtotime('-' . $dias . ' days'));
    }

    /**
     * @param list<string> $where
     * @param array<string, mixed> $params
     */
    private function aplicarAlcanceEmpresaSql(array &$where, array &$params, string $campo): void
    {
        if ($this->esSuperadmin()) {
            return;
        }

        $ids = $this->empresaIdsConsulta();
        if ($ids === []) {
            $where[] = '0=1';

            return;
        }

        $placeholders = [];
        foreach ($ids as $i => $id) {
            $key = 'alcance_' . md5($campo) . '_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $where[] = '(' . $campo . ' IS NULL OR ' . $campo . ' IN (' . implode(',', $placeholders) . '))';
    }

    private function pasaFiltroTipo(string $filtro, string $categoria): bool
    {
        if ($filtro === '') {
            return true;
        }
        if ($filtro === 'cambio') {
            return false; // los logs no son "cambios de datos"
        }
        if ($filtro === 'sistema') {
            return $categoria === 'sistema';
        }

        return $categoria === $filtro;
    }

    /** @param array<string, mixed> $log */
    private function categoriaDesdeLog(array $log): string
    {
        $accion = strtolower((string)($log['accion'] ?? ''));
        $modulo = strtolower((string)($log['modulo'] ?? ''));

        if (str_contains($accion, 'login') || str_contains($accion, 'logout') || $modulo === 'sesiones') {
            return 'acceso';
        }
        if ($modulo === 'operadores' || str_contains($accion, 'operador')) {
            return 'operador';
        }

        return 'sistema';
    }

    /** @param array<string, mixed> $log */
    private function tituloDesdeLog(array $log): string
    {
        $accion = strtolower((string)($log['accion'] ?? ''));
        $modulo = strtolower((string)($log['modulo'] ?? ''));

        return match (true) {
            str_contains($accion, 'login') => 'Inicio de sesión',
            str_contains($accion, 'logout') || str_contains($accion, 'salir') => 'Cierre de sesión',
            $accion === 'create' && $modulo === 'operadores' => 'Alta de operador',
            $accion === 'update' && $modulo === 'operadores' => 'Actualización de operador',
            $accion === 'delete' && $modulo === 'operadores' => 'Baja de operador',
            str_contains($accion, 'status') && $modulo === 'operadores' => 'Cambio de estatus de operador',
            $accion === 'export' || str_contains($accion, 'export') => 'Exportación de reporte',
            $modulo === 'cuentas' => 'Gestión de cuenta',
            $modulo === 'verificaciones' => 'Registro de verificación',
            default => $this->tituloGenerico($accion, $modulo, (string)($log['descripcion'] ?? '')),
        };
    }

    private function tituloGenerico(string $accion, string $modulo, string $descripcion): string
    {
        if ($descripcion !== '') {
            $corta = mb_strlen($descripcion) > 80 ? mb_substr($descripcion, 0, 77) . '…' : $descripcion;

            return $corta;
        }
        $mod = $modulo !== '' ? ucfirst($modulo) : 'Sistema';
        $acc = $accion !== '' ? str_replace('_', ' ', $accion) : 'actividad';

        return $mod . ': ' . $acc;
    }

    /** @param array<string, mixed> $c */
    private function tituloDesdeCambio(array $c): string
    {
        $entidad = strtolower((string)($c['entidad_tipo'] ?? 'registro'));
        $entidadLabel = match ($entidad) {
            'operador' => 'operador',
            'vehiculo' => 'vehículo',
            'empresa' => 'empresa',
            'usuario' => 'usuario',
            default => $entidad,
        };
        $id = (int)($c['entidad_id'] ?? 0);

        return 'Cambio en ' . $entidadLabel . ($id > 0 ? ' #' . $id : '');
    }

    /** @param array<string, mixed> $c */
    private function detalleDesdeCambio(array $c): string
    {
        $campo = $this->etiquetaCampo((string)($c['campo'] ?? ''));
        $antes = $this->valorLegible($c['valor_anterior'] ?? null);
        $despues = $this->valorLegible($c['valor_nuevo'] ?? null);

        return $campo . ': ' . $antes . ' → ' . $despues;
    }

    private function etiquetaCampo(string $campo): string
    {
        return match ($campo) {
            'estatus' => 'Estatus',
            'nombre' => 'Nombre',
            'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno',
            'num_licencia' => 'Núm. licencia',
            'email' => 'Correo',
            'activo' => 'Activo',
            'empresa_id' => 'Empresa',
            'telefono' => 'Teléfono',
            default => $campo !== '' ? ucwords(str_replace('_', ' ', $campo)) : 'Campo',
        };
    }

    private function valorLegible(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '(vacío)';
        }
        $txt = trim((string)$valor);
        if ($txt === '1') {
            return 'Sí';
        }
        if ($txt === '0') {
            return 'No';
        }

        return mb_strlen($txt) > 60 ? mb_substr($txt, 0, 57) . '…' : $txt;
    }

    /** @param array<string, mixed> $row */
    private function nombreUsuario(array $row): string
    {
        $nombre = trim((string)($row['usuario_nombre'] ?? ''));
        if ($nombre !== '') {
            return $nombre;
        }
        $email = trim((string)($row['usuario_email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        return !empty($row['usuario_id']) ? 'Usuario #' . (int)$row['usuario_id'] : 'Sistema';
    }

    private function nombreEmpresa(int $empresaId): string
    {
        if ($empresaId <= 0) {
            return '';
        }
        static $cache = [];
        if (isset($cache[$empresaId])) {
            return $cache[$empresaId];
        }
        $empresas = $this->empresasAccesiblesSesion();
        $cache[$empresaId] = (string)($empresas[$empresaId] ?? ('Empresa #' . $empresaId));

        return $cache[$empresaId];
    }
}
