<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\UsuarioEmpresaService;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Event\EventInterface;

/**
 * Administración de usuarios y asignación de empresas.
 * Acceso: superadmin (rol 1) o consultor (rol 2).
 *
 * Nota: la suspensión de cuentas titulares (consultores) se gestiona en
 * {@see CuentasController::suspender()} y {@see CuentasController::activar()}.
 */
class UsuariosController extends AppController
{
    public const ROL_SUPERADMIN = UsuarioEmpresaService::ROL_SUPERADMIN;
    public const ROL_ADMIN = 2;
    public const ROL_USUARIO = 3;

    /** @var array<int, string> */
    public const ROLES = [
        self::ROL_SUPERADMIN => 'Superadmin',
        self::ROL_ADMIN => 'Consultor',
        self::ROL_USUARIO => 'Usuario',
    ];

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (!$this->puedeAdministrarUsuarios()) {
            throw new ForbiddenException('Solo un administrador puede gestionar usuarios.');
        }
    }

    public function index(): void
    {
        $conn = ConnectionManager::get('default');
        $svc = new UsuarioEmpresaService();
        $esSuper = $this->esSuperadmin();
        $empresaIdsAdmin = $this->empresaIdsAsignables();

        if ($esSuper) {
            $rows = $conn->execute(
                'SELECT u.id, u.nombre, u.apellidos, u.email, u.rol_id, u.empresa_id, u.activo, u.ultimo_acceso
                 FROM usuarios u
                 ORDER BY u.nombre ASC, u.apellidos ASC, u.id ASC',
            )->fetchAll('assoc');
        } elseif ($empresaIdsAdmin === []) {
            $rows = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($empresaIdsAdmin), '?'));
            $rows = $conn->execute(
                "SELECT DISTINCT u.id, u.nombre, u.apellidos, u.email, u.rol_id, u.empresa_id, u.activo, u.ultimo_acceso
                 FROM usuarios u
                 INNER JOIN usuario_empresas ue ON ue.usuario_id = u.id
                 WHERE ue.empresa_id IN ({$placeholders})
                   AND u.rol_id <> ?
                 ORDER BY u.nombre ASC, u.apellidos ASC, u.id ASC",
                array_merge($empresaIdsAdmin, [self::ROL_SUPERADMIN]),
            )->fetchAll('assoc');
        }

        $usuarios = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $empresas = $svc->empresasDeUsuario($id);
            $usuarios[] = [
                'id' => $id,
                'nombre' => (string)$row['nombre'],
                'apellidos' => (string)($row['apellidos'] ?? ''),
                'email' => (string)$row['email'],
                'rol_id' => (int)$row['rol_id'],
                'rol_nombre' => self::ROLES[(int)$row['rol_id']] ?? ('Rol ' . $row['rol_id']),
                'empresa_id' => $row['empresa_id'] !== null ? (int)$row['empresa_id'] : null,
                'activo' => (int)$row['activo'] === 1,
                'ultimo_acceso' => $row['ultimo_acceso'] ?? null,
                'empresas' => $empresas,
            ];
        }

        $this->set(compact('usuarios', 'esSuper'));
        $this->set('roles', self::ROLES);
    }

    public function add(): ?Response
    {
        $empresasOptions = $this->empresasOptionsAsignables();
        $rolesOptions = $this->rolesOptionsCreacion();
        $formData = [
            'nombre' => '',
            'apellidos' => '',
            'email' => '',
            'rol_id' => self::ROL_ADMIN,
            'activo' => 1,
            'empresa_ids' => [],
        ];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $formData = array_merge($formData, [
                'nombre' => trim((string)($data['nombre'] ?? '')),
                'apellidos' => trim((string)($data['apellidos'] ?? '')),
                'email' => strtolower(trim((string)($data['email'] ?? ''))),
                'rol_id' => (int)($data['rol_id'] ?? self::ROL_ADMIN),
                'activo' => !empty($data['activo']) ? 1 : 0,
                'empresa_ids' => $this->normalizarEmpresaIds($data['empresa_ids'] ?? []),
            ]);
            $password = (string)($data['password'] ?? '');
            $passwordConfirm = (string)($data['password_confirm'] ?? '');

            $error = $this->validarUsuarioForm($formData, $password, $passwordConfirm, true, $empresasOptions, $rolesOptions);
            if ($error !== null) {
                $this->Flash->error($error);
            } else {
                $conn = ConnectionManager::get('default');
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
                        $conn->insert('usuarios', [
                            'parent_id' => $this->usuarioIdSesion(),
                            'empresa_id' => $empresaPrincipal,
                            'rol_id' => $formData['rol_id'],
                            'nombre' => $formData['nombre'],
                            'apellidos' => $formData['apellidos'] !== '' ? $formData['apellidos'] : null,
                            'email' => $formData['email'],
                            'password_hash' => $hash,
                            'activo' => $formData['activo'],
                        ]);

                        $row = $conn->execute(
                            'SELECT id FROM usuarios WHERE email = :email LIMIT 1',
                            ['email' => $formData['email']],
                        )->fetch('assoc');
                        $nuevoId = (int)($row['id'] ?? 0);

                        if ($nuevoId > 0) {
                            (new UsuarioEmpresaService())->sincronizarVinculos(
                                $nuevoId,
                                $formData['empresa_ids'],
                                true,
                            );
                            $this->Flash->success('Acceso creado y vinculado a la(s) empresa(s) asignada(s).');

                            return $this->redirect(['action' => 'index']);
                        }
                        $this->Flash->error('No se pudo crear el usuario.');
                    }
                }
            }
        }

        $this->set([
            'formData' => $formData,
            'empresasOptions' => $empresasOptions,
            'rolesOptions' => $rolesOptions,
            'isEdit' => false,
        ]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $conn = ConnectionManager::get('default');
        $svc = new UsuarioEmpresaService();
        $row = $conn->execute(
            'SELECT id, nombre, apellidos, email, rol_id, empresa_id, activo
             FROM usuarios WHERE id = :id LIMIT 1',
            ['id' => $id],
        )->fetch('assoc');

        if (empty($row)) {
            $this->Flash->error('Usuario no encontrado.');

            return $this->redirect(['action' => 'index']);
        }

        if (!$this->puedeEditarUsuario((int)$row['id'], (int)$row['rol_id'])) {
            $this->Flash->error('No puedes editar este usuario.');

            return $this->redirect(['action' => 'index']);
        }

        $empresasOptions = $this->empresasOptionsAsignables();
        $rolesOptions = $this->rolesOptionsCreacion((int)$row['rol_id']);
        $empresasActuales = array_keys($svc->empresasDeUsuario($id));

        $formData = [
            'nombre' => (string)$row['nombre'],
            'apellidos' => (string)($row['apellidos'] ?? ''),
            'email' => (string)$row['email'],
            'rol_id' => (int)$row['rol_id'],
            'activo' => (int)$row['activo'],
            'empresa_ids' => $empresasActuales,
        ];

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $formData = [
                'nombre' => trim((string)($data['nombre'] ?? '')),
                'apellidos' => trim((string)($data['apellidos'] ?? '')),
                'email' => strtolower(trim((string)($data['email'] ?? ''))),
                'rol_id' => (int)($data['rol_id'] ?? $formData['rol_id']),
                'activo' => !empty($data['activo']) ? 1 : 0,
                'empresa_ids' => $this->normalizarEmpresaIds($data['empresa_ids'] ?? []),
            ];
            $password = (string)($data['password'] ?? '');
            $passwordConfirm = (string)($data['password_confirm'] ?? '');

            $error = $this->validarUsuarioForm($formData, $password, $passwordConfirm, false, $empresasOptions, $rolesOptions);
            if ($error !== null) {
                $this->Flash->error($error);
            } else {
                $exists = $conn->execute(
                    'SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1',
                    ['email' => $formData['email'], 'id' => $id],
                )->fetch('assoc');
                if (!empty($exists)) {
                    $this->Flash->error('Ya existe otro usuario con ese correo.');
                } else {
                    $params = [
                        'nombre' => $formData['nombre'],
                        'apellidos' => $formData['apellidos'] !== '' ? $formData['apellidos'] : null,
                        'email' => $formData['email'],
                        'rol_id' => $formData['rol_id'],
                        'activo' => $formData['activo'],
                        'id' => $id,
                    ];
                    $sql = 'UPDATE usuarios
                            SET nombre = :nombre, apellidos = :apellidos, email = :email,
                                rol_id = :rol_id, activo = :activo';
                    if ($password !== '') {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        if (!is_string($hash) || $hash === '') {
                            $this->Flash->error('No se pudo generar la contraseña.');
                            $this->set(compact('formData', 'empresasOptions', 'rolesOptions'));
                            $this->set('isEdit', true);
                            $this->set('usuarioId', $id);

                            return null;
                        }
                        $sql .= ', password_hash = :password_hash';
                        $params['password_hash'] = $hash;
                    }
                    $sql .= ' WHERE id = :id';
                    $conn->execute($sql, $params);

                    // Admin no-super: conserva vínculos a empresas fuera de su alcance
                    $idsFinales = $formData['empresa_ids'];
                    if (!$this->esSuperadmin()) {
                        $todas = array_keys($svc->empresasDeUsuario($id));
                        $fueraDeAlcance = array_values(array_diff($todas, array_keys($empresasOptions)));
                        $idsFinales = array_values(array_unique(array_merge($fueraDeAlcance, $idsFinales)));
                    }

                    $svc->sincronizarVinculos($id, $idsFinales, true);

                    $this->Flash->success('Acceso actualizado.');

                    return $this->redirect(['action' => 'index']);
                }
            }
        }

        $this->set([
            'formData' => $formData,
            'empresasOptions' => $empresasOptions,
            'rolesOptions' => $rolesOptions,
            'isEdit' => true,
            'usuarioId' => $id,
        ]);

        return null;
    }

    /**
     * Activa o desactiva un acceso (campo activo).
     * Para cuentas titulares con historial de suspensión, usar CuentasController.
     */
    public function suspender(int $id): Response
    {
        return $this->toggleActivoUsuario($id, false);
    }

    public function activar(int $id): Response
    {
        return $this->toggleActivoUsuario($id, true);
    }

    private function toggleActivoUsuario(int $id, bool $activar): Response
    {
        $this->request->allowMethod(['post']);

        if ($id === (int)$this->usuarioIdSesion()) {
            $this->Flash->error('No puedes modificar tu propio acceso desde aquí.');
            return $this->redirect(['action' => 'index']);
        }

        $conn = ConnectionManager::get('default');
        $row = $conn->execute(
            'SELECT id, rol_id FROM usuarios WHERE id = :id LIMIT 1',
            ['id' => $id],
        )->fetch('assoc');

        if (empty($row) || !$this->puedeEditarUsuario($id, (int)$row['rol_id'])) {
            $this->Flash->error('No puedes modificar este usuario.');
            return $this->redirect(['action' => 'index']);
        }

        $conn->execute(
            'UPDATE usuarios SET activo = :activo WHERE id = :id',
            ['activo' => $activar ? 1 : 0, 'id' => $id],
        );

        $this->Flash->success($activar ? 'Acceso activado.' : 'Acceso suspendido.');

        return $this->redirect(['action' => 'index']);
    }

    private function puedeAdministrarUsuarios(): bool
    {
        $rol = $this->rolIdSesion();

        return $rol === self::ROL_SUPERADMIN || $rol === self::ROL_ADMIN;
    }

    private function puedeEditarUsuario(int $usuarioId, int $rolObjetivo): bool
    {
        if ($usuarioId === (int)$this->usuarioIdSesion()) {
            return true;
        }
        if ($this->esSuperadmin()) {
            return true;
        }
        if ($rolObjetivo === self::ROL_SUPERADMIN) {
            return false;
        }

        $svc = new UsuarioEmpresaService();
        $empresasObjetivo = array_keys($svc->empresasDeUsuario($usuarioId));
        $empresasAdmin = $this->empresaIdsAsignables();

        return $empresasObjetivo !== [] && array_intersect($empresasObjetivo, $empresasAdmin) !== [];
    }

    /** @return list<int> */
    private function empresaIdsAsignables(): array
    {
        return array_map('intval', array_keys($this->empresasOptionsAsignables()));
    }

    /** @return array<int, string> */
    private function empresasOptionsAsignables(): array
    {
        $svc = new UsuarioEmpresaService();
        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return [];
        }

        return $svc->empresasAccesibles($usuarioId, $this->rolIdSesion());
    }

    /**
     * @param int|null $rolActual Incluir rol actual aunque el editor no pueda crearlo
     * @return array<int, string>
     */
    private function rolesOptionsCreacion(?int $rolActual = null): array
    {
        if ($this->esSuperadmin()) {
            return self::ROLES;
        }

        $roles = [
            self::ROL_ADMIN => self::ROLES[self::ROL_ADMIN],
            self::ROL_USUARIO => self::ROLES[self::ROL_USUARIO],
        ];
        if ($rolActual === self::ROL_SUPERADMIN) {
            $roles[self::ROL_SUPERADMIN] = self::ROLES[self::ROL_SUPERADMIN];
        }

        return $roles;
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
     * @param array<int, string> $rolesOptions
     */
    private function validarUsuarioForm(
        array $formData,
        string $password,
        string $passwordConfirm,
        bool $passwordObligatoria,
        array $empresasOptions,
        array $rolesOptions,
    ): ?string {
        if ($formData['nombre'] === '' || $formData['email'] === '') {
            return 'Completa nombre y correo.';
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Correo electrónico inválido.';
        }
        if (!isset($rolesOptions[$formData['rol_id']])) {
            return 'Rol no permitido.';
        }
        if (!$this->esSuperadmin() && $formData['rol_id'] === self::ROL_SUPERADMIN) {
            return 'No puedes asignar el rol Superadmin.';
        }

        $empresaIds = $formData['empresa_ids'];
        if ($formData['rol_id'] !== self::ROL_SUPERADMIN && $empresaIds === []) {
            return 'Debes asignar al menos una empresa que controlará este usuario.';
        }
        foreach ($empresaIds as $empresaId) {
            if (!isset($empresasOptions[$empresaId])) {
                return 'Seleccionaste una empresa a la que no tienes acceso.';
            }
        }

        if ($passwordObligatoria || $password !== '') {
            if (strlen($password) < 8) {
                return 'La contraseña debe tener al menos 8 caracteres.';
            }
            if ($password !== $passwordConfirm) {
                return 'Las contraseñas no coinciden.';
            }
        }

        return null;
    }
}
