<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\NotificacionesService;
use Cake\Http\Response;

/**
 * Bandeja de notificaciones in-app (JSON).
 */
class NotificacionesController extends AppController
{
    public function panel(): Response
    {
        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return $this->jsonResponse(['total' => 0, 'items' => [], 'hint' => 'sin_sesion']);
        }

        $svc = new NotificacionesService();
        // Rellena bandeja con alertas abiertas de empresas accesibles (p. ej. Super Admin).
        $svc->asegurarDesdeAlertasAbiertas($usuarioId, $this->empresaIdsConsulta());

        $rows = $svc->listarRecientes($usuarioId, 50);
        $items = [];
        foreach ($rows as $row) {
            $url = $row['url_destino'] !== null ? (string)$row['url_destino'] : null;
            if ($url !== null && $url !== '') {
                $url = $svc->normalizarUrlApp($url);
            }
            $items[] = [
                'id' => (int)$row['id'],
                'titulo' => (string)$row['titulo'],
                'mensaje' => (string)$row['mensaje'],
                'url' => $url,
                'leida' => !empty($row['leida']),
                'created' => isset($row['created_at']) ? (string)$row['created_at'] : null,
            ];
        }

        $noLeidas = array_values(array_filter($items, static fn(array $i): bool => !$i['leida']));

        return $this->jsonResponse([
            'total' => count($noLeidas),
            'items' => $items,
        ]);
    }

    public function marcarLeida(): Response
    {
        $this->request->allowMethod(['post']);

        $usuarioId = $this->usuarioIdSesion();
        $id = (int)$this->request->getData('id', 0);
        if ($id <= 0) {
            $json = $this->request->input('json_decode', true);
            if (is_array($json)) {
                $id = (int)($json['id'] ?? 0);
            }
        }

        if ($usuarioId === null || $id <= 0) {
            return $this->jsonResponse(['ok' => false, 'error' => 'Solicitud inválida'], 400);
        }

        $ok = (new NotificacionesService())->marcarLeida($id, $usuarioId);

        return $this->jsonResponse(['ok' => $ok]);
    }

    public function marcarTodas(): Response
    {
        $this->request->allowMethod(['post']);

        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return $this->jsonResponse(['ok' => false, 'error' => 'Sesión inválida'], 401);
        }

        $n = (new NotificacionesService())->marcarTodasLeidas($usuarioId);

        return $this->jsonResponse(['ok' => true, 'marcadas' => $n]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withDisabledCache()
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
