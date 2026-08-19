<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuditoriaService;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\Http\Cookie\Cookie;
use Cake\Routing\Router;

/**
 * Sesiones Controller
 *
 * Logout simple (no plugin Authentication).
 */
class SesionesController extends AppController
{
    public function login(): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        $this->request->allowMethod(['get', 'post']);

        $session = $this->request->getSession();

        // Si ya está logueado, mandar al dashboard
        $already = $session->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);
        if (!empty($already) && $this->request->is('get')) {
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }

        $next = (string)$this->request->getQuery('next', '');
        if ($next !== '' && $this->request->is('get')) {
            $session->write('Auth.next', $next);
        }

        if ($this->request->is('post')) {
            // Rate limiting: bloqueo de 15 min tras 5 intentos fallidos
            $lockUntil = (int)$session->read('Auth.lockUntil', 0);
            if ($lockUntil > time()) {
                $remaining = (int)ceil(($lockUntil - time()) / 60);
                $label = $remaining === 1 ? 'minuto' : 'minutos';
                $this->Flash->error("Demasiados intentos fallidos. Intenta de nuevo en {$remaining} {$label}.");
                $this->set(compact('next'));
                return null;
            }
            if ($lockUntil > 0) {
                $session->delete('Auth.loginAttempts');
                $session->delete('Auth.lockUntil');
            }

            $u = trim((string)$this->request->getData('usuario'));
            $p = (string)$this->request->getData('password');

            $nextPost = (string)$this->request->getData('next', '');
            if ($nextPost !== '') {
                $session->write('Auth.next', $nextPost);
            }

            if ($u === '' || $p === '') {
                $this->Flash->error('Ingresa usuario y contraseña.');
                $this->set(compact('next'));
                return null;
            }

            // ── Autenticación principal: DB (tabla `usuarios`) ────────────────────
            $valid = false;
            $authUser = null;

            try {
                $conn = ConnectionManager::get('default');
                $sql = 'SELECT id, parent_id, empresa_id, rol_id, nombre, apellidos, email, password_hash, activo';
                try {
                    $rows = $conn->execute(
                        $sql . ', suspendido_at FROM usuarios WHERE email = :email LIMIT 1',
                        ['email' => $u],
                    )->fetchAll('assoc');
                } catch (\Throwable) {
                    $rows = $conn->execute(
                        $sql . ' FROM usuarios WHERE email = :email LIMIT 1',
                        ['email' => $u],
                    )->fetchAll('assoc');
                }

                if (!empty($rows)) {
                    $row = $rows[0];
                    $isActive = (int)($row['activo'] ?? 0) === 1;
                    $suspendido = !empty($row['suspendido_at']);

                    if (!$isActive || $suspendido) {
                        $this->Flash->error('Cuenta suspendida.');
                        $this->set(compact('next'));
                        return null;
                    }

                    $hash = (string)($row['password_hash'] ?? '');

                    if ($hash !== '' && password_verify($p, $hash)) {
                        $valid = true;
                        $authUser = [
                            'id' => (int)$row['id'],
                            'parent_id' => $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
                            'empresa_id' => $row['empresa_id'] !== null ? (int)$row['empresa_id'] : null,
                            'rol_id' => (int)($row['rol_id'] ?? 3),
                            'username' => (string)$row['email'],
                            'email' => (string)$row['email'],
                            'nombre' => (string)$row['nombre'],
                            'apellidos' => (string)($row['apellidos'] ?? ''),
                        ];

                        // Registrar último acceso (no bloquea el login si falla)
                        try {
                            $conn->execute(
                                'UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id',
                                ['id' => (int)$row['id']]
                            );
                        } catch (\Throwable $e) {
                            // noop
                        }
                    }
                }
            } catch (\Throwable $e) {
                $valid = false;
                $authUser = null;
            }

            // ── Fallback opcional: AuthSimple (configurable) ─────────────────────
            if (!$valid) {
                $enabled = (bool)Configure::read('AuthSimple.enabled', false);
                $cfgUser = (string)Configure::read('AuthSimple.user', 'admin');
                $cfgPass = (string)Configure::read('AuthSimple.pass', 'admin');

                if ($enabled && hash_equals($cfgUser, $u) && hash_equals($cfgPass, $p)) {
                    $valid = true;
                    $authUser = ['username' => $u];
                }
            }

            if (!$valid) {
                $attempts = (int)$session->read('Auth.loginAttempts', 0) + 1;
                if ($attempts >= 5) {
                    $session->write('Auth.lockUntil', time() + 15 * 60);
                    $session->delete('Auth.loginAttempts');
                    $this->Flash->error('Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.');
                } else {
                    $session->write('Auth.loginAttempts', $attempts);
                    $this->Flash->error('Usuario o contraseña inválidos.');
                }
                $this->set(compact('next'));
                return null;
            }

            // Autenticación exitosa: limpiar contadores y regenerar sesión
            $session->delete('Auth.loginAttempts');
            $session->delete('Auth.lockUntil');

            if (function_exists('session_regenerate_id')) {
                @session_regenerate_id(true);
            }

            $_SESSION['Auth']['User'] = $authUser ?? ['username' => $u];
            $session->write('Auth.User', $_SESSION['Auth']['User']);
            $session->write('Auth.mostrarModalVencidos', true);

            if (class_exists(AuditoriaService::class) && is_array($authUser)) {
                (new AuditoriaService())->registrarLog(
                    'login',
                    'sesiones',
                    'Inicio de sesión: ' . (string)($authUser['email'] ?? $u),
                    ['usuario_id' => (int)($authUser['id'] ?? 0)],
                    isset($authUser['id']) ? (int)$authUser['id'] : null,
                    isset($authUser['empresa_id']) ? (int)$authUser['empresa_id'] : null,
                );
            }

            $this->Flash->success('Bienvenido.');
            $redir = (string)$session->consume('Auth.next');
            if ($redir !== '' && str_starts_with($redir, '/')) {
                return $this->redirect($redir);
            }

            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }

        $this->set(compact('next'));
        return null;
    }

    public function registro(): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        $this->request->allowMethod(['get', 'post']);

        $session = $this->request->getSession();
        $already = $session->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);
        if (!empty($already) && $this->request->is('get')) {
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            $nombre = trim((string)$this->request->getData('nombre'));
            $apellidos = trim((string)$this->request->getData('apellidos'));
            $email = mb_strtolower(trim((string)$this->request->getData('email')));
            $password = (string)$this->request->getData('password');
            $passwordConfirm = (string)$this->request->getData('password_confirm');

            if ($nombre === '' || $email === '' || $password === '') {
                $this->Flash->error('Completa nombre, correo y contraseña.');
                return null;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->Flash->error('Correo electrónico inválido.');
                return null;
            }
            if (strlen($password) < 8) {
                $this->Flash->error('La contraseña debe tener al menos 8 caracteres.');
                return null;
            }
            if ($password !== $passwordConfirm) {
                $this->Flash->error('Las contraseñas no coinciden.');
                return null;
            }

            $conn = ConnectionManager::get('default');
            $exists = $conn->execute(
                'SELECT id FROM usuarios WHERE email = :email LIMIT 1',
                ['email' => $email],
            )->fetch('assoc');
            if (!empty($exists)) {
                $this->Flash->error('Ya existe una cuenta con ese correo. Inicia sesión.');
                return null;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            if (!is_string($hash) || $hash === '') {
                $this->Flash->error('No se pudo crear la cuenta. Intenta de nuevo.');
                return null;
            }

            $conn->insert('usuarios', [
                'parent_id' => null,
                'empresa_id' => null,
                'rol_id' => 2,
                'nombre' => $nombre,
                'apellidos' => $apellidos !== '' ? $apellidos : null,
                'email' => $email,
                'password_hash' => $hash,
                'activo' => 1,
            ]);

            $row = $conn->execute(
                'SELECT id, parent_id, empresa_id, rol_id, nombre, apellidos, email
                 FROM usuarios WHERE email = :email LIMIT 1',
                ['email' => $email],
            )->fetch('assoc');

            if (empty($row)) {
                $this->Flash->error('No se pudo crear la cuenta.');
                return null;
            }

            if (function_exists('session_regenerate_id')) {
                @session_regenerate_id(true);
            }

            $authUser = [
                'id' => (int)$row['id'],
                'parent_id' => null,
                'empresa_id' => null,
                'rol_id' => (int)$row['rol_id'],
                'username' => (string)$row['email'],
                'email' => (string)$row['email'],
                'nombre' => (string)$row['nombre'],
                'apellidos' => (string)($row['apellidos'] ?? ''),
            ];
            $_SESSION['Auth']['User'] = $authUser;
            $session->write('Auth.User', $authUser);
            $session->write('Auth.mostrarModalVencidos', false);

            $this->Flash->success('Cuenta creada. Registra tu primera empresa para comenzar.');

            return $this->redirect(['controller' => 'Empresas', 'action' => 'index', '?' => ['nueva' => '1']]);
        }

        return null;
    }

    public function salir(): Response
    {
        // Permite salir incluso si el usuario abre la URL directamente.
        $this->request->allowMethod(['get', 'post']);

        $session = $this->request->getSession();
        $authUser = $session->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);

        // Limpia cualquier rastro de auth legacy (el sistema usa $_SESSION['Auth'] en utilities).
        if (isset($_SESSION) && is_array($_SESSION)) {
            unset($_SESSION['Auth'], $_SESSION['User'], $_SESSION['Usuario']);
        }

        // Vaciar + destruir la sesión de Cake
        $session->delete('Auth.mostrarModalVencidos');
        $session->delete('Auth.modalVencidosMostradoEnSesion');
        $session->clear();
        $session->destroy();

        // Expirar cookie(s) de sesión para que el navegador no la reutilice.
        // Importante: debe expirar con el mismo path/base que usa la app.
        $basePath = (string)($this->request->getAttribute('base') ?? '');
        $cookiePath = $basePath !== '' ? $basePath . '/' : '/';
        $cookiePath = preg_replace('#/+#', '/', $cookiePath) ?: '/';

        $cookieNames = array_values(array_unique(array_filter([
            (string)Configure::read('Session.cookie'),
            (string)(ini_get('session.name') ?: ''),
            'PHPSESSID',
        ])));

        $response = $this->response;
        foreach ($cookieNames as $name) {
            $response = $response->withExpiredCookie(new Cookie($name, '', null, $cookiePath));
        }

        // Regenerar id del lado PHP para evitar reutilización en el mismo request (si aplica).
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        if (class_exists(AuditoriaService::class) && is_array($authUser)) {
            (new AuditoriaService())->registrarLog(
                'logout',
                'sesiones',
                'Cierre de sesión: ' . (string)($authUser['email'] ?? $authUser['username'] ?? ''),
                ['usuario_id' => (int)($authUser['id'] ?? 0)],
                isset($authUser['id']) ? (int)$authUser['id'] : null,
                isset($authUser['empresa_id']) ? (int)$authUser['empresa_id'] : null,
            );
        }

        $this->Flash->success('Sesión finalizada.');

        // En Controller no existe $this->Url (es helper de View). Usamos Router para construir URL.
        return $response->withLocation(Router::url(['controller' => 'Sesiones', 'action' => 'login']));
    }
}

