<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ConnectionManager;

/**
 * Registro de eventos en sistema_logs y auditoria_cambios.
 */
class AuditoriaService
{
    /**
     * Historial campo a campo (valor anterior → nuevo).
     */
    public function registrarCambio(
        string $entidadTipo,
        int $entidadId,
        string $campo,
        mixed $valorAnterior,
        mixed $valorNuevo,
        ?int $usuarioId = null,
        ?int $empresaId = null,
        ?string $ip = null,
    ): void {
        if ((string)$valorAnterior === (string)$valorNuevo) {
            return;
        }

        try {
            $conn = ConnectionManager::get('default');
            $conn->insert('auditoria_cambios', [
                'empresa_id' => $empresaId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => mb_substr($entidadTipo, 0, 60),
                'entidad_id' => $entidadId,
                'campo' => mb_substr($campo, 0, 80),
                'valor_anterior' => $this->serializarValor($valorAnterior),
                'valor_nuevo' => $this->serializarValor($valorNuevo),
                'ip' => $ip ?? $this->clientIp(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // No bloquear flujo principal.
        }
    }

    /**
     * Compara dos arrays de campos y registra cada diferencia.
     *
     * @param array<string, mixed> $antes
     * @param array<string, mixed> $despues
     * @param list<string> $campos
     */
    public function registrarCambiosEntidad(
        string $entidadTipo,
        int $entidadId,
        array $antes,
        array $despues,
        array $campos,
        ?int $usuarioId = null,
        ?int $empresaId = null,
    ): void {
        foreach ($campos as $campo) {
            $this->registrarCambio(
                $entidadTipo,
                $entidadId,
                $campo,
                $antes[$campo] ?? null,
                $despues[$campo] ?? null,
                $usuarioId,
                $empresaId,
            );
        }
    }

    /**
     * Alias de {@see registrar()} para compatibilidad con llamadas registrarLog().
     *
     * @param array<string, mixed>|null $detalle
     */
    public function registrarLog(
        string $accion,
        string $modulo,
        string $descripcion,
        ?array $detalle = null,
        ?int $usuarioId = null,
        ?int $empresaId = null,
        string $nivel = 'info',
    ): void {
        $this->registrar($accion, $modulo, $descripcion, $detalle, $usuarioId, $empresaId, $nivel);
    }

    private function serializarValor(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE) ?: null;
        }

        return mb_substr((string)$valor, 0, 65000);
    }

    /**
     * @param array<string, mixed>|null $detalle
     */
    public function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?array $detalle = null,
        ?int $usuarioId = null,
        ?int $empresaId = null,
        string $nivel = 'info',
    ): void {
        try {
            $conn = ConnectionManager::get('default');
            $conn->insert('sistema_logs', [
                'usuario_id' => $usuarioId,
                'empresa_id' => $empresaId,
                'accion' => mb_substr($accion, 0, 60),
                'modulo' => mb_substr($modulo, 0, 60),
                'descripcion' => mb_substr($descripcion, 0, 500),
                'detalle_json' => $detalle !== null ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $this->clientIp(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? mb_substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255)
                    : null,
                'nivel' => in_array($nivel, ['info', 'warning', 'error'], true) ? $nivel : 'info',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // No bloquear flujo principal si falla el log.
        }
    }

    private function clientIp(): ?string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string)$_SERVER[$key])[0]);

                return mb_substr($ip, 0, 45);
            }
        }

        return null;
    }
}
