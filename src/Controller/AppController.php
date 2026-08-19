<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Model\Table\DocumentosVehiculoTable;
use App\Service\DocumentosVencidosService;
use App\Service\UsuarioEmpresaService;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $ctrl = (string)$this->request->getParam('controller');

        // Permitir acceso al login/logout/registro sin sesión.
        if ($ctrl === 'Sesiones' && in_array((string)$this->request->getParam('action'), ['login', 'registro', 'salir'], true)) {
            return;
        }

        // Gate mínimo basado en la sesión legacy usada por utilidades (Auth.User).
        $session = $this->request->getSession();
        $user = $session->read('Auth.User');
        if (empty($user) && empty($_SESSION['Auth']['User'])) {
            $this->Flash->error('Tu sesión expiró. Inicia sesión de nuevo.');
            $event->setResult($this->redirect(['controller' => 'Sesiones', 'action' => 'login']));

            return;
        }

        if (
            !$session->read('Auth.modalVencidosMostradoEnSesion')
            && !$session->read('Auth.mostrarModalVencidos')
        ) {
            $session->write('Auth.mostrarModalVencidos', true);
        }

        // Si llega ?empresa_id= válido en GET, usarlo solo como filtro de vista.
        // NO cambiar la empresa activa de sesión aquí: eso hacía que el Panel "filtrara mal"
        // y, con action incorrecta del formulario, sacaba al usuario del /saf/.
        // La empresa activa se cambia solo con EmpresasController::seleccionar (sidebar).

    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        if ($this->request->getParam('controller') === 'Sesiones') {
            return;
        }
        $layout = $this->viewBuilder()->getLayout() ?? 'default';
        if ($layout !== 'default') {
            return;
        }

        $session = $this->request->getSession();

        // Mantener alertas al día en todas las pantallas (campanita = módulo Alertas).
        try {
            $this->sincronizarAlertasEmpresasAccesibles();
        } catch (\Throwable $e) {
            // No bloquear la carga si falla la sincronización de alertas.
        }

        if (!$session->read('Auth.mostrarModalVencidos')) {
            return;
        }

        $lista = (new DocumentosVencidosService())->listarParaModal($this->empresaIdsConsulta());
        if ($lista === []) {
            $session->delete('Auth.mostrarModalVencidos');
            $session->write('Auth.modalVencidosMostradoEnSesion', true);

            return;
        }

        $session->consume('Auth.mostrarModalVencidos');
        $session->write('Auth.modalVencidosMostradoEnSesion', true);
        $this->set('documentosVencidosModal', $lista);
        $this->set('mostrarModalDocumentosVencidos', true);
    }

    /** @return array<string, mixed>|null */
    protected function usuarioSesion(): ?array
    {
        $session = $this->request->getSession();
        $user = $session->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);

        return is_array($user) ? $user : null;
    }

    protected function usuarioIdSesion(): ?int
    {
        $user = $this->usuarioSesion();
        $id = is_array($user) ? (int)($user['id'] ?? 0) : 0;

        return $id > 0 ? $id : null;
    }

    protected function rolIdSesion(): ?int
    {
        $user = $this->usuarioSesion();

        return is_array($user) ? (int)($user['rol_id'] ?? 0) : null;
    }

    protected function esSuperadmin(): bool
    {
        return $this->rolIdSesion() === UsuarioEmpresaService::ROL_SUPERADMIN;
    }

    /** Super Admin o Consultor (cuenta administradora). */
    protected function esAdministrador(): bool
    {
        $rol = $this->rolIdSesion();

        return $rol === UsuarioEmpresaService::ROL_SUPERADMIN
            || $rol === UsuarioEmpresaService::ROL_CONSULTOR;
    }

    /**
     * Empresa activa del usuario en sesión.
     */
    protected function empresaIdSesion(): ?int
    {
        $session = $this->request->getSession();
        $authUser = $this->usuarioSesion();
        $usuarioId = $this->usuarioIdSesion();
        $rolId = $this->rolIdSesion();
        $service = new UsuarioEmpresaService();

        $empresaId = null;
        if (is_array($authUser) && !empty($authUser['empresa_id'])) {
            $empresaId = (int)$authUser['empresa_id'];
        }

        if ($empresaId !== null && $empresaId > 0 && $usuarioId !== null && !$service->tieneAcceso($usuarioId, $rolId, $empresaId)) {
            $empresaId = null;
        }

        if ($empresaId === null || $empresaId <= 0) {
            $accesibles = $usuarioId !== null ? $service->idsEmpresasAccesibles($usuarioId, $rolId) : [];
            $empresaId = $accesibles[0] ?? null;
            if ($empresaId !== null && $empresaId > 0) {
                $this->establecerEmpresaActiva($empresaId);
            }
        }

        return ($empresaId !== null && $empresaId > 0) ? $empresaId : null;
    }

    /** @return array<int, string> */
    protected function empresasAccesiblesSesion(): array
    {
        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return [];
        }

        return (new UsuarioEmpresaService())->empresasAccesibles($usuarioId, $this->rolIdSesion());
    }

    protected function establecerEmpresaActiva(int $empresaId): void
    {
        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return;
        }

        $service = new UsuarioEmpresaService();
        if (!$service->tieneAcceso($usuarioId, $this->rolIdSesion(), $empresaId)) {
            return;
        }

        $session = $this->request->getSession();
        $authUser = $this->usuarioSesion() ?? [];
        $authUser['empresa_id'] = $empresaId;
        $session->write('Auth.User', $authUser);
        $_SESSION['Auth']['User'] = $authUser;

        $service->fijarEmpresaPrincipal($usuarioId, $empresaId);
    }

    protected function usuarioTieneAccesoEmpresa(int $empresaId): bool
    {
        $usuarioId = $this->usuarioIdSesion();
        if ($usuarioId === null) {
            return false;
        }

        return (new UsuarioEmpresaService())->tieneAcceso($usuarioId, $this->rolIdSesion(), $empresaId);
    }

    /** @return list<int> Empresas visibles para el usuario en sesión. */
    protected function empresaIdsConsulta(): array
    {
        $ids = array_map('intval', array_keys($this->empresasAccesiblesSesion()));
        if ($ids !== []) {
            return $ids;
        }
        $activa = $this->empresaIdSesion();

        return ($activa !== null && $activa > 0) ? [$activa] : [];
    }

    /**
     * Filtro opcional ?empresa_id= para vistas consolidadas.
     * null = todas las empresas accesibles.
     */
    protected function filtroEmpresaIdRequest(): ?int
    {
        $raw = $this->request->getQuery('empresa_id');
        if ($raw === null || $raw === '' || (string)$raw === 'todas' || (string)$raw === '0') {
            return null;
        }
        $id = (int)$raw;
        if ($id <= 0 || !$this->usuarioTieneAccesoEmpresa($id)) {
            return null;
        }

        return $id;
    }

    /**
     * IDs para listados: todas las accesibles, o una si viene ?empresa_id=.
     *
     * @return list<int>
     */
    protected function empresaIdsAlcance(): array
    {
        $filtro = $this->filtroEmpresaIdRequest();
        if ($filtro !== null) {
            return [$filtro];
        }

        return $this->empresaIdsConsulta();
    }

    /**
     * Datos de filtro multiempresa para las vistas.
     *
     * @return array{empresasFiltro: array<int, string>, filtroEmpresaId: int|null, vistaConsolidada: bool, alcanceLabel: string}
     */
    protected function datosFiltroEmpresaVista(): array
    {
        $empresas = $this->empresasAccesiblesSesion();
        $filtro = $this->filtroEmpresaIdRequest();
        $vistaConsolidada = $filtro === null && count($empresas) > 1;

        if ($filtro !== null && isset($empresas[$filtro])) {
            $alcanceLabel = (string)$empresas[$filtro];
        } elseif ($vistaConsolidada) {
            $alcanceLabel = 'Todas tus empresas (' . count($empresas) . ')';
        } elseif ($empresas !== []) {
            $alcanceLabel = (string)reset($empresas);
        } else {
            $alcanceLabel = 'Sin empresas';
        }

        return [
            'empresasFiltro' => $empresas,
            'filtroEmpresaId' => $filtro,
            'vistaConsolidada' => $vistaConsolidada,
            'alcanceLabel' => $alcanceLabel,
        ];
    }

    /**
     * Aplica filtro de empresa(s) accesibles a un query.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     */
    protected function aplicarAlcanceEmpresa($query, string $campo): void
    {
        $ids = $this->empresaIdsAlcance();
        if ($ids === []) {
            $query->where(['0 = 1']);

            return;
        }
        if (count($ids) === 1) {
            $query->where([$campo => $ids[0]]);

            return;
        }
        $query->where([$campo . ' IN' => $ids]);
    }

    protected function entidadEnAlcanceEmpresa(?int $empresaId): bool
    {
        if ($empresaId === null || $empresaId <= 0) {
            return false;
        }

        return in_array($empresaId, $this->empresaIdsConsulta(), true);
    }

    /**
     * Empresa activa de sesión (nunca la primera del sistema).
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function obtenerEmpresaActiva()
    {
        $empresaId = $this->empresaIdSesion();
        if ($empresaId === null || $empresaId <= 0) {
            return null;
        }

        try {
            return $this->fetchTable('Empresas')->get($empresaId);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<string> */
    protected function rfcsEmpresasAlcance(): array
    {
        $ids = $this->empresaIdsAlcance();
        if ($ids === []) {
            return [];
        }

        $rows = $this->fetchTable('Empresas')
            ->find()
            ->select(['rfc'])
            ->where(['id IN' => $ids])
            ->all();

        $rfcs = [];
        foreach ($rows as $row) {
            $rfc = strtoupper(trim((string)($row->rfc ?? '')));
            if ($rfc !== '') {
                $rfcs[] = $rfc;
            }
        }

        return array_values(array_unique($rfcs));
    }

    /** @return array<int, string> */
    protected function vehiculosListAlcance(bool $soloActivos = true): array
    {
        $query = $this->fetchTable('Vehiculos')
            ->find('list', keyField: 'id', valueField: 'placas')
            ->orderBy(['Vehiculos.placas' => 'ASC']);
        if ($soloActivos) {
            $query->where(['Vehiculos.estatus' => 'activo']);
        }
        $this->aplicarAlcanceEmpresa($query, 'Vehiculos.empresa_id');

        return $query->all()->toArray();
    }

    /** @return array<int, string> */
    protected function operadoresListAlcance(bool $soloActivos = true): array
    {
        $query = $this->fetchTable('Operadores')
            ->find('list', keyField: 'id', valueField: 'nombre')
            ->orderBy(['Operadores.nombre' => 'ASC']);
        if ($soloActivos) {
            $query->where(['Operadores.estatus' => 'activo']);
        }
        $this->aplicarAlcanceEmpresa($query, 'Operadores.empresa_id');

        return $query->all()->toArray();
    }

    /** @return array<int, string> */
    protected function empresasListAlcance(): array
    {
        return $this->empresasAccesiblesSesion();
    }

    protected function vehiculoEnAlcance(int $vehiculoId): bool
    {
        if ($vehiculoId <= 0) {
            return false;
        }
        try {
            $veh = $this->fetchTable('Vehiculos')->get($vehiculoId, fields: ['id', 'empresa_id']);
        } catch (\Throwable) {
            return false;
        }

        return $this->entidadEnAlcanceEmpresa((int)$veh->empresa_id);
    }

    protected function operadorEnAlcance(int $operadorId): bool
    {
        if ($operadorId <= 0) {
            return false;
        }
        try {
            $op = $this->fetchTable('Operadores')->get($operadorId, fields: ['id', 'empresa_id']);
        } catch (\Throwable) {
            return false;
        }

        return $this->entidadEnAlcanceEmpresa((int)$op->empresa_id);
    }

    protected function comprobanteEnAlcance(object $comprobante): bool
    {
        $schema = $this->fetchTable('Comprobantes')->getSchema();
        if ($schema->hasColumn('empresa_id') && !empty($comprobante->empresa_id)) {
            return $this->entidadEnAlcanceEmpresa((int)$comprobante->empresa_id);
        }
        $rfc = strtoupper(trim((string)($comprobante->rfcEmisor ?? '')));
        if ($rfc === '') {
            return false;
        }

        return in_array($rfc, $this->rfcsDeEmpresasConsulta(), true);
    }

    /** @return list<string> RFCs de todas las empresas accesibles (sin filtro ?empresa_id=). */
    protected function rfcsDeEmpresasConsulta(): array
    {
        $ids = $this->empresaIdsConsulta();
        if ($ids === []) {
            return [];
        }
        $rows = $this->fetchTable('Empresas')
            ->find()
            ->select(['rfc'])
            ->where(['id IN' => $ids])
            ->all();
        $rfcs = [];
        foreach ($rows as $row) {
            $rfc = strtoupper(trim((string)($row->rfc ?? '')));
            if ($rfc !== '') {
                $rfcs[] = $rfc;
            }
        }

        return array_values(array_unique($rfcs));
    }

    protected function sincronizarAlertasEmpresasAccesibles(): void
    {
        try {
            $alertas = $this->fetchTable('Alertas');
            foreach ($this->empresaIdsConsulta() as $empresaId) {
                $alertas->sincronizarDesdeDocumentos($empresaId);
            }
        } catch (\Throwable) {
            // No bloquear la UI si falla la sincronización.
        }
    }

    /**
     * Código de retorno tras guardar documentos.
     * Formato: vehiculo:ID:tab | operador:ID | empresa | adicional | cumplimiento | cumplimiento-mp
     */
    protected function codigoOrigenDocumento(): ?string
    {
        $origen = trim((string)$this->request->getQuery('origen', ''));
        if ($origen === '') {
            $origen = trim((string)$this->request->getData('origen', ''));
        }

        return $origen !== '' ? $origen : null;
    }

    protected function codigoOrigenVehiculo(int $vehiculoId, string $tab = 'docs'): string
    {
        $tab = in_array($tab, ['docs', 'mp', 'mant', 'hist'], true) ? $tab : 'docs';

        return 'vehiculo:' . $vehiculoId . ':' . $tab;
    }

    protected function codigoOrigenOperador(int $operadorId): string
    {
        return 'operador:' . $operadorId;
    }

    protected function tabVehiculoParaTipoDocumento(string $tipo): string
    {
        return in_array($tipo, DocumentosVehiculoTable::documentosRequeridosMaterialesPeligrosos(), true)
            ? 'mp'
            : 'docs';
    }

    /** @return array<string, mixed>|null */
    protected function destinoDesdeOrigen(string $origen): ?array
    {
        $partes = explode(':', $origen, 3);

        return match ($partes[0] ?? '') {
            'vehiculo' => [
                'controller' => 'Vehiculos',
                'action' => 'view',
                (int)($partes[1] ?? 0),
                '?' => ['tab' => in_array($partes[2] ?? 'docs', ['docs', 'mp', 'mant', 'hist'], true) ? $partes[2] : 'docs'],
            ],
            'operador' => ['controller' => 'Operadores', 'action' => 'edit', (int)($partes[1] ?? 0)],
            'empresa' => ['controller' => 'Empresas', 'action' => 'index'],
            'adicional' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            'cumplimiento-mp' => ['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos'],
            'cumplimiento' => ['controller' => 'Cumplimiento', 'action' => 'index'],
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $default
     */
    protected function redirectTrasDocumento(?string $origen, array $default): Response
    {
        $origen ??= $this->codigoOrigenDocumento();
        if ($origen !== null) {
            $destino = $this->destinoDesdeOrigen($origen);
            if ($destino !== null) {
                $raiz = explode(':', $origen, 2)[0];
                if (in_array($raiz, ['vehiculo', 'operador'], true)) {
                    if ((int)($destino[2] ?? 0) > 0) {
                        return $this->redirect($destino);
                    }
                } else {
                    return $this->redirect($destino);
                }
            }
        }

        return $this->redirect($default);
    }

    /** @return array<string, mixed> */
    protected function urlDesdeOrigen(?string $origen, array $default): array
    {
        if ($origen !== null) {
            $destino = $this->destinoDesdeOrigen($origen);
            if ($destino !== null) {
                $raiz = explode(':', $origen, 2)[0];
                if (in_array($raiz, ['vehiculo', 'operador'], true)) {
                    if ((int)($destino[2] ?? 0) > 0) {
                        return $destino;
                    }
                } else {
                    return $destino;
                }
            }
        }

        return $default;
    }

    protected function inferirOrigenVehiculo(?int $vehiculoId, ?string $tipoDocumento = null): ?string
    {
        $existente = $this->codigoOrigenDocumento();
        if ($existente !== null && str_starts_with($existente, 'vehiculo:')) {
            return $existente;
        }
        if ($vehiculoId === null || $vehiculoId <= 0) {
            return null;
        }
        $tab = $tipoDocumento !== null && $tipoDocumento !== ''
            ? $this->tabVehiculoParaTipoDocumento($tipoDocumento)
            : 'docs';

        return $this->codigoOrigenVehiculo($vehiculoId, $tab);
    }
}
