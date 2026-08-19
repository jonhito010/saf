<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ConnectionManager;

class UsuarioEmpresaService
{
    public const ROL_SUPERADMIN = 1;
    public const ROL_CONSULTOR = 2;
    public const ROL_USUARIO = 3;

    public function vincular(int $usuarioId, int $empresaId, bool $esPropietario = true): void
    {
        if ($usuarioId <= 0 || $empresaId <= 0) {
            return;
        }

        $conn = ConnectionManager::get('default');
        $conn->execute(
            'INSERT INTO usuario_empresas (usuario_id, empresa_id, es_propietario)
             VALUES (:usuario_id, :empresa_id, :es_propietario)
             ON DUPLICATE KEY UPDATE es_propietario = VALUES(es_propietario)',
            [
                'usuario_id' => $usuarioId,
                'empresa_id' => $empresaId,
                'es_propietario' => $esPropietario ? 1 : 0,
            ],
        );
    }

    /** @return list<int> */
    public function idsEmpresasAccesibles(int $usuarioId, ?int $rolId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $conn = ConnectionManager::get('default');

        if ($rolId === self::ROL_SUPERADMIN) {
            $rows = $conn->execute(
                'SELECT id FROM empresas WHERE estatus = :estatus ORDER BY id ASC',
                ['estatus' => 'activa'],
            )->fetchAll('assoc');

            return array_map(static fn(array $row): int => (int)$row['id'], $rows);
        }

        $rows = $conn->execute(
            'SELECT ue.empresa_id
             FROM usuario_empresas ue
             INNER JOIN empresas e ON e.id = ue.empresa_id
             WHERE ue.usuario_id = :usuario_id AND e.estatus = :estatus
             ORDER BY e.nombre ASC',
            [
                'usuario_id' => $usuarioId,
                'estatus' => 'activa',
            ],
        )->fetchAll('assoc');

        return array_map(static fn(array $row): int => (int)$row['empresa_id'], $rows);
    }

    /** @return array<int, string> */
    public function empresasAccesibles(int $usuarioId, ?int $rolId): array
    {
        $ids = $this->idsEmpresasAccesibles($usuarioId, $rolId);
        if ($ids === []) {
            return [];
        }

        $conn = ConnectionManager::get('default');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $conn->execute(
            "SELECT id, nombre FROM empresas WHERE id IN ({$placeholders}) ORDER BY nombre ASC",
            $ids,
        )->fetchAll('assoc');

        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row['id']] = (string)$row['nombre'];
        }

        return $out;
    }

    public function tieneAcceso(int $usuarioId, ?int $rolId, int $empresaId): bool
    {
        if ($empresaId <= 0 || $usuarioId <= 0) {
            return false;
        }

        if ($rolId === self::ROL_SUPERADMIN) {
            return true;
        }

        $conn = ConnectionManager::get('default');
        $row = $conn->execute(
            'SELECT 1 AS ok
             FROM usuario_empresas
             WHERE usuario_id = :usuario_id AND empresa_id = :empresa_id
             LIMIT 1',
            [
                'usuario_id' => $usuarioId,
                'empresa_id' => $empresaId,
            ],
        )->fetch('assoc');

        return !empty($row);
    }

    public function fijarEmpresaPrincipal(int $usuarioId, int $empresaId): void
    {
        if ($usuarioId <= 0 || $empresaId <= 0) {
            return;
        }

        ConnectionManager::get('default')->execute(
            'UPDATE usuarios SET empresa_id = :empresa_id WHERE id = :usuario_id',
            [
                'usuario_id' => $usuarioId,
                'empresa_id' => $empresaId,
            ],
        );
    }

    /** @return array<int, string> id => nombre */
    public function empresasDeUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $conn = ConnectionManager::get('default');
        $rows = $conn->execute(
            'SELECT e.id, e.nombre
             FROM usuario_empresas ue
             INNER JOIN empresas e ON e.id = ue.empresa_id
             WHERE ue.usuario_id = :usuario_id
             ORDER BY e.nombre ASC',
            ['usuario_id' => $usuarioId],
        )->fetchAll('assoc');

        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row['id']] = (string)$row['nombre'];
        }

        return $out;
    }

    /**
     * Reemplaza los vínculos del usuario con la lista dada.
     *
     * @param list<int> $empresaIds
     */
    public function sincronizarVinculos(int $usuarioId, array $empresaIds, bool $esPropietario = true): void
    {
        if ($usuarioId <= 0) {
            return;
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $empresaIds),
            static fn(int $id): bool => $id > 0,
        )));

        $conn = ConnectionManager::get('default');

        if ($ids === []) {
            $conn->execute(
                'DELETE FROM usuario_empresas WHERE usuario_id = :usuario_id',
                ['usuario_id' => $usuarioId],
            );
            $conn->execute(
                'UPDATE usuarios SET empresa_id = NULL WHERE id = :usuario_id',
                ['usuario_id' => $usuarioId],
            );

            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$usuarioId], $ids);
        $conn->execute(
            "DELETE FROM usuario_empresas
             WHERE usuario_id = ?
               AND empresa_id NOT IN ({$placeholders})",
            $params,
        );

        foreach ($ids as $empresaId) {
            $this->vincular($usuarioId, $empresaId, $esPropietario);
        }

        $principal = $ids[0];
        $actual = $conn->execute(
            'SELECT empresa_id FROM usuarios WHERE id = :id LIMIT 1',
            ['id' => $usuarioId],
        )->fetch('assoc');
        $actualId = isset($actual['empresa_id']) ? (int)$actual['empresa_id'] : 0;
        if ($actualId <= 0 || !in_array($actualId, $ids, true)) {
            $this->fijarEmpresaPrincipal($usuarioId, $principal);
        }
    }
}
