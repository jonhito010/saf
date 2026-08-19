<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

class NotificacionesService
{
    public const ROL_SUPERADMIN = 1;
    public const ROL_CONSULTOR = 2;

    /**
     * @param array<string, mixed> $alerta
     * @param list<int> $usuarioIds
     */
    public function crearDesdeAlerta(array $alerta, array $usuarioIds): int
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $usuarioIds),
            static fn(int $id): bool => $id > 0,
        )));
        if ($ids === []) {
            return 0;
        }

        $titulo = (string)($alerta['titulo'] ?? $alerta['descripcion'] ?? 'Alerta de cumplimiento');
        $mensaje = (string)($alerta['mensaje'] ?? $alerta['descripcion'] ?? $titulo);
        $empresaId = isset($alerta['empresa_id']) ? (int)$alerta['empresa_id'] : null;
        $alertaId = isset($alerta['id']) ? (int)$alerta['id'] : null;
        $urlDestino = isset($alerta['url_destino']) ? (string)$alerta['url_destino'] : null;
        if ($urlDestino !== null && $urlDestino !== '') {
            $urlDestino = $this->normalizarUrlApp($urlDestino);
        }
        $tipo = (string)($alerta['tipo'] ?? 'alerta');
        $now = DateTime::now()->format('Y-m-d H:i:s');

        $conn = ConnectionManager::get('default');
        $creadas = 0;

        foreach ($ids as $usuarioId) {
            try {
                if ($alertaId !== null && $alertaId > 0 && $this->existeParaUsuarioAlerta($usuarioId, $alertaId)) {
                    continue;
                }
                $conn->insert('notificaciones', [
                    'usuario_id' => $usuarioId,
                    'empresa_id' => $empresaId > 0 ? $empresaId : null,
                    'alerta_id' => $alertaId > 0 ? $alertaId : null,
                    'titulo' => mb_substr($titulo, 0, 200),
                    'mensaje' => mb_substr($mensaje, 0, 500),
                    'url_destino' => $urlDestino,
                    'tipo' => mb_substr($tipo, 0, 40),
                    'leida' => 0,
                    'leida_at' => null,
                    'created_at' => $now,
                ]);
                $creadas++;
            } catch (\Throwable) {
                // Continuar con el siguiente destinatario.
            }
        }

        return $creadas;
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }

        try {
            $row = ConnectionManager::get('default')->execute(
                'SELECT COUNT(*) AS total
                 FROM notificaciones
                 WHERE usuario_id = :usuario_id AND leida = 0',
                ['usuario_id' => $usuarioId],
            )->fetch('assoc');

            return (int)($row['total'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarRecientes(int $usuarioId, int $limit = 10): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT id, usuario_id, empresa_id, alerta_id, titulo, mensaje,
                        url_destino, tipo, leida, leida_at, created_at
                 FROM notificaciones
                 WHERE usuario_id = :usuario_id
                 ORDER BY leida ASC, created_at DESC, id DESC
                 LIMIT ' . $limit,
                ['usuario_id' => $usuarioId],
            )->fetchAll('assoc');

            return array_map(static function (array $row): array {
                $row['id'] = (int)$row['id'];
                $row['usuario_id'] = (int)$row['usuario_id'];
                $row['empresa_id'] = $row['empresa_id'] !== null ? (int)$row['empresa_id'] : null;
                $row['alerta_id'] = $row['alerta_id'] !== null ? (int)$row['alerta_id'] : null;
                $row['leida'] = (int)$row['leida'] === 1;

                return $row;
            }, $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Crea en la bandeja del usuario las alertas abiertas que aún no tiene.
     * Así Super Admin / consultores nuevos ven lo mismo que el módulo Alertas.
     *
     * @param list<int> $empresaIds
     */
    public function asegurarDesdeAlertasAbiertas(int $usuarioId, array $empresaIds): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }

        $empresaIds = array_values(array_unique(array_filter(
            array_map('intval', $empresaIds),
            static fn(int $id): bool => $id > 0,
        )));
        if ($empresaIds === []) {
            return 0;
        }

        try {
            $conn = ConnectionManager::get('default');
            $placeholders = implode(',', array_fill(0, count($empresaIds), '?'));
            $alertas = $conn->execute(
                "SELECT id, empresa_id, descripcion, tipo_documento_label, url_destino, tipo_alerta
                 FROM alertas_cumplimiento
                 WHERE resuelto = 0
                   AND empresa_id IN ($placeholders)
                 ORDER BY id DESC
                 LIMIT 100",
                $empresaIds,
            )->fetchAll('assoc');
        } catch (\Throwable) {
            return 0;
        }

        $creadas = 0;
        foreach ($alertas as $alerta) {
            $titulo = (string)($alerta['tipo_documento_label'] ?: $alerta['descripcion'] ?: 'Alerta');
            $mensaje = (string)($alerta['descripcion'] ?: $titulo);
            $creadas += $this->crearDesdeAlerta([
                'id' => (int)$alerta['id'],
                'empresa_id' => (int)$alerta['empresa_id'],
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url_destino' => $alerta['url_destino'] !== null && $alerta['url_destino'] !== ''
                    ? (string)$alerta['url_destino']
                    : '/alertas/ir/' . (int)$alerta['id'],
                'tipo' => (string)($alerta['tipo_alerta'] ?? 'alerta'),
            ], [$usuarioId]);
        }

        return $creadas;
    }

    public function marcarLeida(int $notifId, int $usuarioId): bool
    {
        if ($notifId <= 0 || $usuarioId <= 0) {
            return false;
        }

        try {
            $now = DateTime::now()->format('Y-m-d H:i:s');
            $stmt = ConnectionManager::get('default')->execute(
                'UPDATE notificaciones
                 SET leida = 1, leida_at = :leida_at
                 WHERE id = :id AND usuario_id = :usuario_id AND leida = 0',
                [
                    'id' => $notifId,
                    'usuario_id' => $usuarioId,
                    'leida_at' => $now,
                ],
            );

            return $stmt->rowCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function marcarTodasLeidas(int $usuarioId): int
    {
        if ($usuarioId <= 0) {
            return 0;
        }

        try {
            $now = DateTime::now()->format('Y-m-d H:i:s');
            $stmt = ConnectionManager::get('default')->execute(
                'UPDATE notificaciones
                 SET leida = 1, leida_at = :leida_at
                 WHERE usuario_id = :usuario_id AND leida = 0',
                [
                    'usuario_id' => $usuarioId,
                    'leida_at' => $now,
                ],
            );

            return (int)$stmt->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Destinatarios: flujo activo + siempre Super Admin (ven todas las empresas).
     *
     * @return list<int>
     */
    public function destinatariosParaEmpresa(int $empresaId): array
    {
        if ($empresaId <= 0) {
            return [];
        }

        try {
            $conn = ConnectionManager::get('default');

            $flujo = $conn->execute(
                'SELECT rol_id, orden
                 FROM notificaciones_flujo
                 WHERE activo = 1
                   AND (empresa_id IS NULL OR empresa_id = :empresa_id)
                 ORDER BY orden ASC, rol_id ASC',
                ['empresa_id' => $empresaId],
            )->fetchAll('assoc');

            if ($flujo === []) {
                $flujo = [
                    ['rol_id' => self::ROL_CONSULTOR, 'orden' => 1],
                    ['rol_id' => self::ROL_SUPERADMIN, 'orden' => 2],
                ];
            }

            $ordenados = [];
            $vistos = [];

            foreach ($flujo as $paso) {
                $rolId = (int)$paso['rol_id'];
                if ($rolId === self::ROL_SUPERADMIN) {
                    foreach ($this->idsSuperadminsActivos() as $uid) {
                        if (!isset($vistos[$uid])) {
                            $vistos[$uid] = true;
                            $ordenados[] = $uid;
                        }
                    }
                    continue;
                }

                $usuarios = $conn->execute(
                    'SELECT DISTINCT u.id
                     FROM usuarios u
                     INNER JOIN usuario_empresas ue ON ue.usuario_id = u.id
                     WHERE ue.empresa_id = :empresa_id
                       AND u.rol_id = :rol_id
                       AND u.activo = 1
                     ORDER BY u.id ASC',
                    [
                        'empresa_id' => $empresaId,
                        'rol_id' => $rolId,
                    ],
                )->fetchAll('assoc');

                foreach ($usuarios as $usuario) {
                    $uid = (int)$usuario['id'];
                    if ($uid > 0 && !isset($vistos[$uid])) {
                        $vistos[$uid] = true;
                        $ordenados[] = $uid;
                    }
                }
            }

            // Siempre incluir Super Admin aunque el flujo lo tenga inactivo.
            foreach ($this->idsSuperadminsActivos() as $uid) {
                if (!isset($vistos[$uid])) {
                    $vistos[$uid] = true;
                    $ordenados[] = $uid;
                }
            }

            return $ordenados;
        } catch (\Throwable) {
            return $this->idsSuperadminsActivos();
        }
    }

    /**
     * @return list<int>
     */
    private function idsSuperadminsActivos(): array
    {
        try {
            $rows = ConnectionManager::get('default')->execute(
                'SELECT id FROM usuarios WHERE rol_id = :rol AND activo = 1 ORDER BY id ASC',
                ['rol' => self::ROL_SUPERADMIN],
            )->fetchAll('assoc');

            return array_values(array_filter(array_map(
                static fn(array $r): int => (int)$r['id'],
                $rows,
            )));
        } catch (\Throwable) {
            return [];
        }
    }

    private function existeParaUsuarioAlerta(int $usuarioId, int $alertaId): bool
    {
        try {
            $row = ConnectionManager::get('default')->execute(
                'SELECT id FROM notificaciones
                 WHERE usuario_id = :uid AND alerta_id = :aid
                 LIMIT 1',
                ['uid' => $usuarioId, 'aid' => $alertaId],
            )->fetch('assoc');

            return is_array($row);
        } catch (\Throwable) {
            return false;
        }
    }

    public function normalizarUrlApp(?string $url): string
    {
        $url = trim((string)$url);
        if ($url === '' || $url === '#') {
            return $url;
        }
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }
        $base = rtrim((string)(Configure::read('App.base') ?: ''), '/');
        if ($base !== '' && !str_starts_with($url, $base . '/') && $url !== $base) {
            return $base . $url;
        }

        return $url;
    }
}
