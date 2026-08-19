<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuditoriaService;
use App\Service\UsuarioEmpresaService;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * Gestión de cuentas titulares (superadmin).
 */
class CuentasController extends AppController
{
    public const ROL_CONSULTOR = 2;

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (!$this->esSuperadmin()) {
            throw new ForbiddenException('Solo el superadministrador puede gestionar cuentas.');
        }
    }

    public function index(): void
    {
        $conn = ConnectionManager::get('default');
        $svc = new UsuarioEmpresaService();

        try {
            $rows = $conn->execute(
                'SELECT u.id, u.nombre, u.apellidos, u.email, u.rol_id, u.empresa_id,
                        u.activo, u.es_cuenta, u.ultimo_acceso, u.suspendido_at, u.motivo_suspension
                 FROM usuarios u
                 WHERE u.es_cuenta = 1 OR u.rol_id IN (1, 2)
                 ORDER BY u.es_cuenta DESC, u.nombre ASC, u.apellidos ASC, u.id ASC',
            )->fetchAll('assoc');
        } catch (\Throwable) {
            $rows = $conn->execute(
                'SELECT u.id, u.nombre, u.apellidos, u.email, u.rol_id, u.empresa_id,
                        u.activo, u.ultimo_acceso
                 FROM usuarios u
                 WHERE u.rol_id IN (1, 2)
                 ORDER BY u.nombre ASC, u.apellidos ASC, u.id ASC',
            )->fetchAll('assoc');
        }

        $cuentas = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $cuentas[] = [
                'id' => $id,
                'nombre' => (string)$row['nombre'],
                'apellidos' => (string)($row['apellidos'] ?? ''),
                'email' => (string)$row['email'],
                'rol_id' => (int)$row['rol_id'],
                'rol_nombre' => UsuariosController::ROLES[(int)$row['rol_id']] ?? ('Rol ' . $row['rol_id']),
                'activo' => (int)$row['activo'] === 1,
                'es_cuenta' => (int)($row['es_cuenta'] ?? 0) === 1,
                'ultimo_acceso' => $row['ultimo_acceso'] ?? null,
                'suspendido_at' => $row['suspendido_at'] ?? null,
                'motivo_suspension' => $row['motivo_suspension'] ?? null,
                'empresas' => $svc->empresasDeUsuario($id),
            ];
        }

        $this->set(compact('cuentas'));
    }

    public function suspender(int $id): Response
    {
        $this->request->allowMethod(['post']);

        return $this->toggleSuspension($id, true);
    }

    public function activar(int $id): Response
    {
        $this->request->allowMethod(['post']);

        return $this->toggleSuspension($id, false);
    }

    public function crear(): ?Response
    {
        $conn = ConnectionManager::get('default');
        $svc = new UsuarioEmpresaService();
        $empresasOptions = $svc->empresasAccesibles(
            (int)$this->usuarioIdSesion(),
            $this->rolIdSesion(),
        );

        $formData = [
            'nombre' => '',
            'apellidos' => '',
            'email' => '',
            'activo' => 1,
            'empresa_ids' => [],
        ];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $formData = [
                'nombre' => trim((string)($data['nombre'] ?? '')),
                'apellidos' => trim((string)($data['apellidos'] ?? '')),
                'email' => strtolower(trim((string)($data['email'] ?? ''))),
                'activo' => !empty($data['activo']) ? 1 : 0,
                'empresa_ids' => $this->normalizarEmpresaIds($data['empresa_ids'] ?? []),
            ];
            $password = (string)($data['password'] ?? '');
            $passwordConfirm = (string)($data['password_confirm'] ?? '');

            $error = $this->validarCuentaForm($formData, $password, $passwordConfirm, $empresasOptions);
            if ($error !== null) {
                $this->Flash->error($error);
            } else {
                $exists = $conn->execute(
                    'SELECT id FROM usuarios WHERE email = :email LIMIT 1',
                    ['email' => $formData['email']],
                )->fetch('assoc');
                if (!empty($exists)) {
                    $this->Flash->error('Ya existe un usuario con ese correo.');
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    if (!is_string($hash) || $hash === '') {
                        $this->Flash->error('No se pudo generar la contraseña.');
                    } else {
                        $empresaPrincipal = $formData['empresa_ids'][0] ?? null;
                        $insertData = [
                            'parent_id' => $this->usuarioIdSesion(),
                            'empresa_id' => $empresaPrincipal,
                            'rol_id' => self::ROL_CONSULTOR,
                            'nombre' => $formData['nombre'],
                            'apellidos' => $formData['apellidos'] !== '' ? $formData['apellidos'] : null,
                            'email' => $formData['email'],
                            'password_hash' => $hash,
                            'activo' => $formData['activo'],
                        ];

                        try {
                            $conn->insert('usuarios', $insertData + ['es_cuenta' => 1]);
                        } catch (\Throwable) {
                            $conn->insert('usuarios', $insertData);
                        }

                        $row = $conn->execute(
                            'SELECT id FROM usuarios WHERE email = :email LIMIT 1',
                            ['email' => $formData['email']],
                        )->fetch('assoc');
                        $nuevoId = (int)($row['id'] ?? 0);

                        if ($nuevoId > 0) {
                            $svc->sincronizarVinculos($nuevoId, $formData['empresa_ids'], true);

                            if (class_exists(AuditoriaService::class)) {
                                (new AuditoriaService())->registrar(
                                    'create',
                                    'cuentas',
                                    'Cuenta consultor creada: ' . $formData['email'],
                                    ['usuario_id' => $nuevoId],
                                    $this->usuarioIdSesion(),
                                    $empresaPrincipal,
                                );
                            }

                            $this->Flash->success('Cuenta creada correctamente.');

                            return $this->redirect(['action' => 'index']);
                        }
                        $this->Flash->error('No se pudo crear la cuenta.');
                    }
                }
            }
        }

        $this->set(compact('formData', 'empresasOptions'));

        return null;
    }

    private function toggleSuspension(int $id, bool $suspender): Response
    {
        if ($id === (int)$this->usuarioIdSesion()) {
            $this->Flash->error('No puedes suspender tu propia cuenta.');

            return $this->redirect(['action' => 'index']);
        }

        $conn = ConnectionManager::get('default');
        $row = $conn->execute(
            'SELECT id, email, activo FROM usuarios WHERE id = :id LIMIT 1',
            ['id' => $id],
        )->fetch('assoc');

        if (empty($row)) {
            $this->Flash->error('Cuenta no encontrada.');

            return $this->redirect(['action' => 'index']);
        }

        $motivo = trim((string)$this->request->getData('motivo', ''));
        $now = date('Y-m-d H:i:s');

        try {
            if ($suspender) {
                $conn->execute(
                    'UPDATE usuarios SET activo = 0, suspendido_at = :now, motivo_suspension = :motivo WHERE id = :id',
                    ['now' => $now, 'motivo' => $motivo !== '' ? $motivo : null, 'id' => $id],
                );
                $this->Flash->success('Cuenta suspendida.');
            } else {
                $conn->execute(
                    'UPDATE usuarios SET activo = 1, suspendido_at = NULL, motivo_suspension = NULL WHERE id = :id',
                    ['id' => $id],
                );
                $this->Flash->success('Cuenta activada.');
            }
        } catch (\Throwable) {
            $conn->execute(
                'UPDATE usuarios SET activo = :activo WHERE id = :id',
                ['activo' => $suspender ? 0 : 1, 'id' => $id],
            );
            $this->Flash->success($suspender ? 'Cuenta suspendida.' : 'Cuenta activada.');
        }

        if (class_exists(AuditoriaService::class)) {
            (new AuditoriaService())->registrar(
                $suspender ? 'suspend' : 'activate',
                'cuentas',
                ($suspender ? 'Suspensión' : 'Activación') . ' de cuenta: ' . ($row['email'] ?? ''),
                ['usuario_id' => $id, 'motivo' => $motivo],
                $this->usuarioIdSesion(),
            );
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizarEmpresaIds(mixed $raw): array
    {
        if (!is_array($raw)) {
            $raw = $raw !== null && $raw !== '' ? [$raw] : [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $raw),
            static fn(int $id): bool => $id > 0,
        )));
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<int, string> $empresasOptions
     */
    private function validarCuentaForm(
        array $formData,
        string $password,
        string $passwordConfirm,
        array $empresasOptions,
    ): ?string {
        if ($formData['nombre'] === '' || $formData['email'] === '') {
            return 'Completa nombre y correo.';
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Correo electrónico inválido.';
        }
        if ($formData['empresa_ids'] === []) {
            return 'Asigna al menos una empresa a la cuenta.';
        }
        foreach ($formData['empresa_ids'] as $empresaId) {
            if (!isset($empresasOptions[$empresaId])) {
                return 'Seleccionaste una empresa no válida.';
            }
        }
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $passwordConfirm) {
            return 'Las contraseñas no coinciden.';
        }

        return null;
    }
}
