<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\TiposDocumentoTable;
use App\Service\AlertasReglasService;
use App\Service\UsuarioEmpresaService;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * CRUD de reglas de alertas (alertas_reglas) por tipo de documento.
 */
class ReglasAlertasController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $rol = $this->rolIdSesion();
        if ($rol !== UsuarioEmpresaService::ROL_SUPERADMIN && $rol !== 2) {
            throw new ForbiddenException('No tienes permiso para gestionar reglas de alertas.');
        }
    }

    public function index(): void
    {
        $this->asegurarColumnasDocumento();
        $conn = ConnectionManager::get('default');
        try {
            $reglas = $conn->execute(
                'SELECT id, dias_antes, nombre, ambito, tipo_documento, enviar_alerta, ocultar_despues,
                        mantener_visible, nivel_urgencia, activo, orden, empresa_id, created_at, updated_at
                 FROM alertas_reglas
                 ORDER BY orden ASC, dias_antes DESC, id ASC',
            )->fetchAll('assoc');
        } catch (\Throwable) {
            // BD sin columnas nuevas todavía
            try {
                $reglas = $conn->execute(
                    'SELECT id, dias_antes, nombre, enviar_alerta, ocultar_despues, mantener_visible,
                            nivel_urgencia, activo, orden, empresa_id, created_at, updated_at
                     FROM alertas_reglas
                     ORDER BY orden ASC, dias_antes DESC, id ASC',
                )->fetchAll('assoc');
                foreach ($reglas as &$r) {
                    $r['ambito'] = 'todos';
                    $r['tipo_documento'] = '';
                }
                unset($r);
            } catch (\Throwable) {
                $reglas = [];
            }
        }

        $catalogo = TiposDocumentoTable::catalogo();
        $etiquetas = [];
        foreach ($reglas as $r) {
            $ambito = AlertasReglasService::normalizarAmbito($r['ambito'] ?? 'todos');
            $tipo = trim((string)($r['tipo_documento'] ?? ''));
            $etiquetas[(int)$r['id']] = AlertasReglasService::etiquetaAlcance(
                $ambito,
                $tipo,
                static fn(string $codigo, string $amb): string => $catalogo->etiqueta($codigo, $amb === 'todos' ? null : $amb),
            );
        }

        $this->set(compact('reglas', 'etiquetas'));
    }

    public function add(): ?Response
    {
        $this->asegurarColumnasDocumento();
        $formData = $this->formDataDefault();

        if ($this->request->is('post')) {
            $formData = $this->parseFormData($this->request->getData());
            $error = $this->validarForm($formData);
            if ($error !== null) {
                $this->Flash->error($error);
            } else {
                $conn = ConnectionManager::get('default');
                try {
                    $conn->insert('alertas_reglas', [
                        'dias_antes' => $formData['dias_antes'],
                        'nombre' => $formData['nombre'],
                        'ambito' => $formData['ambito'],
                        'tipo_documento' => $formData['tipo_documento'],
                        'enviar_alerta' => $formData['enviar_alerta'],
                        'ocultar_despues' => $formData['ocultar_despues'],
                        'mantener_visible' => $formData['mantener_visible'],
                        'nivel_urgencia' => $formData['nivel_urgencia'],
                        'activo' => $formData['activo'],
                        'orden' => $formData['dias_antes'],
                        'empresa_id' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    AlertasReglasService::limpiarCache();
                    $this->Flash->success('Regla de alerta creada para el documento indicado.');

                    return $this->redirect(['action' => 'index']);
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'Duplicate') || str_contains($msg, '1062')) {
                        $this->Flash->error('Ya existe una regla con esos días para el mismo documento.');
                    } else {
                        $this->Flash->error('No se pudo guardar la regla. ¿Ejecutaste el SQL de reglas por documento?');
                    }
                }
            }
        }

        $this->setFormCatalogos();
        $this->set(compact('formData'));
        $this->set('isEdit', false);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $this->asegurarColumnasDocumento();
        $conn = ConnectionManager::get('default');
        try {
            $row = $conn->execute(
                'SELECT id, dias_antes, nombre, ambito, tipo_documento, enviar_alerta, ocultar_despues,
                        mantener_visible, nivel_urgencia, activo
                 FROM alertas_reglas WHERE id = :id LIMIT 1',
                ['id' => $id],
            )->fetch('assoc');
        } catch (\Throwable) {
            $row = $conn->execute(
                'SELECT id, dias_antes, nombre, enviar_alerta, ocultar_despues, mantener_visible,
                        nivel_urgencia, activo
                 FROM alertas_reglas WHERE id = :id LIMIT 1',
                ['id' => $id],
            )->fetch('assoc');
            if (is_array($row)) {
                $row['ambito'] = 'todos';
                $row['tipo_documento'] = '';
            }
        }

        if (empty($row)) {
            $this->Flash->error('Regla no encontrada.');

            return $this->redirect(['action' => 'index']);
        }

        $formData = [
            'dias_antes' => (int)$row['dias_antes'],
            'nombre' => (string)$row['nombre'],
            'ambito' => AlertasReglasService::normalizarAmbito($row['ambito'] ?? 'todos'),
            'tipo_documento' => trim((string)($row['tipo_documento'] ?? '')),
            'enviar_alerta' => (int)$row['enviar_alerta'],
            'ocultar_despues' => (int)$row['ocultar_despues'],
            'mantener_visible' => (int)$row['mantener_visible'],
            'nivel_urgencia' => (string)$row['nivel_urgencia'],
            'activo' => (int)$row['activo'],
        ];

        if ($this->request->is(['post', 'put', 'patch'])) {
            $formData = $this->parseFormData($this->request->getData());
            $error = $this->validarForm($formData);
            if ($error !== null) {
                $this->Flash->error($error);
            } else {
                try {
                    $conn->execute(
                        'UPDATE alertas_reglas
                         SET dias_antes = :dias_antes, nombre = :nombre,
                             ambito = :ambito, tipo_documento = :tipo_documento,
                             enviar_alerta = :enviar_alerta, ocultar_despues = :ocultar_despues,
                             mantener_visible = :mantener_visible, nivel_urgencia = :nivel_urgencia,
                             activo = :activo, orden = :orden, updated_at = :updated_at
                         WHERE id = :id',
                        [
                            'dias_antes' => $formData['dias_antes'],
                            'nombre' => $formData['nombre'],
                            'ambito' => $formData['ambito'],
                            'tipo_documento' => $formData['tipo_documento'],
                            'enviar_alerta' => $formData['enviar_alerta'],
                            'ocultar_despues' => $formData['ocultar_despues'],
                            'mantener_visible' => $formData['mantener_visible'],
                            'nivel_urgencia' => $formData['nivel_urgencia'],
                            'activo' => $formData['activo'],
                            'orden' => $formData['dias_antes'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'id' => $id,
                        ],
                    );
                    AlertasReglasService::limpiarCache();
                    $this->Flash->success('Regla actualizada.');

                    return $this->redirect(['action' => 'index']);
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'Duplicate') || str_contains($msg, '1062')) {
                        $this->Flash->error('Ya existe una regla con esos días para el mismo documento.');
                    } else {
                        $this->Flash->error('No se pudo actualizar la regla.');
                    }
                }
            }
        }

        $this->setFormCatalogos();
        $this->set(compact('formData', 'id'));
        $this->set('isEdit', true);

        return null;
    }

    private function setFormCatalogos(): void
    {
        $cat = TiposDocumentoTable::catalogo();
        $tiposPorAmbito = [
            'todos' => ['' => 'Todos los documentos'],
            'empresa' => ['' => 'Todos los de empresa'] + $cat->opcionesEmpresaTodos(),
            'vehiculo' => ['' => 'Todos los de vehículo'] + $cat->opcionesVehiculo(),
            'operador' => ['' => 'Todos los de operador'] + $cat->opcionesOperador(),
        ];
        $ambitos = [
            'todos' => 'Todos los ámbitos',
            'empresa' => 'Documentos de empresa',
            'vehiculo' => 'Documentos de vehículo',
            'operador' => 'Documentos de operador',
        ];
        $this->set(compact('tiposPorAmbito', 'ambitos'));
    }

    /** @return array<string, mixed> */
    private function formDataDefault(): array
    {
        return [
            'dias_antes' => 30,
            'nombre' => '',
            'ambito' => 'operador',
            'tipo_documento' => '',
            'enviar_alerta' => 1,
            'ocultar_despues' => 0,
            'mantener_visible' => 1,
            'nivel_urgencia' => 'advertencia',
            'activo' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function parseFormData(array $data): array
    {
        $ambito = AlertasReglasService::normalizarAmbito((string)($data['ambito'] ?? 'todos'));
        $tipo = trim((string)($data['tipo_documento'] ?? ''));
        if ($ambito === 'todos') {
            $tipo = '';
        }

        return [
            'dias_antes' => max(0, (int)($data['dias_antes'] ?? 0)),
            'nombre' => trim((string)($data['nombre'] ?? '')),
            'ambito' => $ambito,
            'tipo_documento' => $tipo,
            'enviar_alerta' => !empty($data['enviar_alerta']) ? 1 : 0,
            'ocultar_despues' => !empty($data['ocultar_despues']) ? 1 : 0,
            'mantener_visible' => !empty($data['mantener_visible']) ? 1 : 0,
            'nivel_urgencia' => (string)($data['nivel_urgencia'] ?? 'info'),
            'activo' => !empty($data['activo']) ? 1 : 0,
        ];
    }

    /** @param array<string, mixed> $formData */
    private function validarForm(array $formData): ?string
    {
        if ($formData['nombre'] === '') {
            return 'Indica un nombre para la regla.';
        }
        if ($formData['dias_antes'] < 0) {
            return 'Los días antes deben ser 0 o más.';
        }
        if (!in_array($formData['nivel_urgencia'], ['info', 'advertencia', 'critico'], true)) {
            return 'Nivel de urgencia inválido.';
        }
        if (!in_array($formData['ambito'], ['todos', 'empresa', 'vehiculo', 'operador'], true)) {
            return 'Ámbito de documento inválido.';
        }

        $tipo = (string)$formData['tipo_documento'];
        if ($tipo !== '' && $formData['ambito'] !== 'todos') {
            $cat = TiposDocumentoTable::catalogo();
            $opciones = match ($formData['ambito']) {
                'empresa' => $cat->opcionesEmpresaTodos(),
                'vehiculo' => $cat->opcionesVehiculo(),
                'operador' => $cat->opcionesOperador(),
                default => [],
            };
            if (!isset($opciones[$tipo])) {
                return 'El tipo de documento no pertenece al ámbito seleccionado.';
            }
        }

        return null;
    }

    /**
     * Intenta crear columnas en caliente si faltan (entornos sin patch SQL).
     */
    private function asegurarColumnasDocumento(): void
    {
        $conn = ConnectionManager::get('default');
        try {
            $conn->execute('SELECT ambito, tipo_documento FROM alertas_reglas LIMIT 1');
        } catch (\Throwable) {
            try {
                $conn->execute(
                    "ALTER TABLE alertas_reglas
                     ADD COLUMN ambito VARCHAR(20) NOT NULL DEFAULT 'todos' AFTER nombre,
                     ADD COLUMN tipo_documento VARCHAR(60) NOT NULL DEFAULT '' AFTER ambito",
                );
            } catch (\Throwable) {
                // Requiere patch SQL manual en producción.
            }
        }
    }
}
