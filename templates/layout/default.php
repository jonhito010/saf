<?php
/**
 * SAF — Sistema de cumplimiento normativo · Layout principal
 * @var \App\View\AppView $this
 */
$currentController = $this->request->getParam('controller');
$currentAction = (string)$this->request->getParam('action');

$navActive = function (string $ctrl, ?string $action = null) use ($currentController, $currentAction): string {
    if ($currentController !== $ctrl) {
        return '';
    }
    if ($action !== null && $currentAction !== $action) {
        return '';
    }

    return 'active';
};

$extrasControllers = ['Comprobantes', 'Clientes', 'Servicios', 'CatalogoMercancias', 'Ubicaciones'];
$esModuloExtra = in_array($currentController, $extrasControllers, true);

$topbarTitles = [
    'Dashboard'             => 'Panel de control',
    'Empresas'              => 'Documentación empresa',
    'Vehiculos'             => 'Vehículos',
    'Operadores'            => 'Operadores',
    'Bajas'                 => 'Bajas vehiculares',
    'Cumplimiento'          => 'Cumplimiento',
    'DocumentosAdicionales' => 'Documentos adicionales',
    'DocumentosEmpresa'     => 'Documentación empresa',
    'DocumentosVehiculo'    => 'Documentos de vehículo',
    'Comprobantes'          => 'Carta Porte / CFDI',
    'Clientes'              => 'Clientes',
    'Servicios'             => 'Productos y servicios',
    'CatalogoMercancias'    => 'Catálogo de mercancías',
    'Ubicaciones'           => 'Ubicaciones',
    'Alertas'               => 'Alertas',
    'Busqueda'              => 'Búsqueda',
    'Usuarios'              => 'Accesos',
    'TiposDocumento'        => 'Tipos de documento',
    'Verificaciones'        => 'Historial de verificaciones',
    'Reportes'              => 'Reportes',
    'ReglasAlertas'         => 'Reglas de alertas',
    'Auditoria'             => 'Auditoría',
    'Cuentas'               => 'Cuentas',
];
if ($currentController === 'Dashboard' && $currentAction === 'general') {
    $topbarTitle = 'Panel general · Todas las empresas';
} elseif ($currentController === 'Cumplimiento' && $currentAction === 'materialesPeligrosos') {
    $topbarTitle = 'Materiales peligrosos';
} elseif ($currentController === 'Comprobantes' && $currentAction === 'generar') {
    $topbarTitle = 'Generar Carta Porte';
} elseif ($currentController === 'Comprobantes' && $currentAction === 'index') {
    $topbarTitle = 'Comprobantes emitidos';
} else {
    $topbarTitle = $topbarTitles[$currentController] ?? $this->fetch('title');
}

use Cake\ORM\TableRegistry;
use App\Service\UsuarioEmpresaService;
try {
    $authUserLayout = $this->request->getSession()->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);
    $empresasAccesiblesLayout = [];
    $empresaActivaIdLayout = null;
    if (is_array($authUserLayout) && !empty($authUserLayout['id'])) {
        $svcEmp = new UsuarioEmpresaService();
        $empresasAccesiblesLayout = $svcEmp->empresasAccesibles(
            (int)$authUserLayout['id'],
            (int)($authUserLayout['rol_id'] ?? 0),
        );
        $empresaActivaIdLayout = (int)($authUserLayout['empresa_id'] ?? 0);
        if ($empresaActivaIdLayout <= 0 && $empresasAccesiblesLayout !== []) {
            $empresaActivaIdLayout = (int)array_key_first($empresasAccesiblesLayout);
        }
    }

    $alertasTable    = TableRegistry::getTableLocator()->get('Alertas');
    $empresaIdsLayout = array_map('intval', array_keys($empresasAccesiblesLayout));
    if ($empresaIdsLayout === [] && $empresaActivaIdLayout > 0) {
        $empresaIdsLayout = [$empresaActivaIdLayout];
    }
    // Mismo alcance que el módulo Alertas: ?empresa_id= filtra; si no, todas las accesibles.
    $filtroEmpresaQuery = $this->request->getQuery('empresa_id');
    if ($filtroEmpresaQuery !== null && $filtroEmpresaQuery !== '' && (string)$filtroEmpresaQuery !== 'todas' && (string)$filtroEmpresaQuery !== '0') {
        $filtroId = (int)$filtroEmpresaQuery;
        if ($filtroId > 0 && in_array($filtroId, $empresaIdsLayout, true)) {
            $empresaIdsLayout = [$filtroId];
        }
    }
    if ($empresaIdsLayout === []) {
        $alertasTotal = 0;
        $alertasCriticas = 0;
        $alertasRecientesLayout = [];
        $vehiculosAlerta = 0;
    } else {
        $alertasBaseQuery = $alertasTable->find()->where([
            'resuelto' => 0,
            'empresa_id IN' => $empresaIdsLayout,
        ]);
        $alertasTotal    = (clone $alertasBaseQuery)->count();
        $alertasCriticas = (clone $alertasBaseQuery)->where(['nivel_urgencia' => 'critico'])->count();
        // Mismo orden que AlertasController::index (urgencia + fecha de vencimiento).
        $alertasRecientesLayout = (clone $alertasBaseQuery)
            ->orderBy([
                'Alertas.nivel_urgencia' => 'DESC',
                'Alertas.fecha_vencimiento' => 'ASC',
                'Alertas.id' => 'DESC',
            ])
            ->limit(10)
            ->all();
        $vehiculosTable  = TableRegistry::getTableLocator()->get('Vehiculos');
        $vehiculosAlerta = $vehiculosTable->find()
            ->matching('DocumentosVehiculo', fn($q) => $q->where(['DocumentosVehiculo.estatus IN' => ['vencido', 'por_vencer']]))
            ->distinct(['Vehiculos.id'])
            ->where(['Vehiculos.empresa_id IN' => $empresaIdsLayout])
            ->count();
    }
    $empresa = null;
    if ($empresaActivaIdLayout !== null && $empresaActivaIdLayout > 0) {
        $empresa = TableRegistry::getTableLocator()->get('Empresas')->find()->where(['id' => $empresaActivaIdLayout])->first();
    }
    // No usar find()->first() global: evita mostrar otra empresa del sistema.
} catch (\Exception $e) {
    $alertasTotal = $alertasCriticas = $vehiculosAlerta = 0;
    $alertasRecientesLayout = [];
    $empresa = null;
    $authUserLayout = $authUserLayout ?? ($this->request->getSession()->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null));
    $empresasAccesiblesLayout = $empresasAccesiblesLayout ?? [];
    $empresaIdsLayout = $empresaIdsLayout ?? [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="csrfToken" content="<?= h($this->request->getAttribute('csrfToken') ?? '') ?>">
<title>SAF — <?= h($topbarTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<?= $this->Html->css('saf') ?>
</head>
<body>
<div class="app" id="saf-app">

<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="saf-sidebar" aria-label="Menú principal">
  <div class="sidebar-header">
    <div class="logo">
      <div class="logo-img">
        <svg viewBox="0 0 36 36" width="30" height="30" xmlns="http://www.w3.org/2000/svg">
          <rect width="36" height="36" rx="6" fill="#1B3A8C"/>
          <path d="M8 24L14 12L18 20L22 15L28 24H8Z" fill="#2BBFB3"/>
        </svg>
      </div>
      <div class="logo-text">SAF <span>Sistema de cumplimiento normativo</span></div>
    </div>
  </div>

  <?php if ($empresa): ?>
  <div class="sidebar-company">
    <div class="company-label">Empresa activa</div>
    <div class="company-name"><?= h($empresa->nombre) ?></div>
    <div class="company-rfc"><?= h($empresa->rfc) ?></div>
    <?php if (!empty($empresasAccesiblesLayout) && count($empresasAccesiblesLayout) > 1): ?>
      <?= $this->Form->create(null, [
          'url' => ['controller' => 'Empresas', 'action' => 'seleccionar'],
          'id' => 'form-cambiar-empresa',
          'style' => 'margin-top:8px',
      ]) ?>
        <?= $this->Form->control('empresa_id', [
            'label' => false,
            'type' => 'select',
            'options' => $empresasAccesiblesLayout,
            'value' => $empresaActivaIdLayout,
            'class' => 'nav-empresa-select',
            'onchange' => 'this.form.submit()',
        ]) ?>
      <?= $this->Form->end() ?>
      <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index', '?' => ['nueva' => '1']]) ?>" class="nav-empresa-add">+ Nueva empresa</a>
    <?php elseif (!empty($empresasAccesiblesLayout)): ?>
      <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index', '?' => ['nueva' => '1']]) ?>" class="nav-empresa-add">+ Agregar otra empresa</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <nav class="sidebar-nav">
    <div class="nav-group">
      <div class="nav-group-label">Documentación</div>
      <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Dashboard', 'index') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm8-8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
        Panel de control
      </a>
      <?php
        $rolNavDash = is_array($authUserLayout ?? null) ? (int)($authUserLayout['rol_id'] ?? 0) : 0;
        // Administradores: Super Admin (1) y Consultor/cuenta (2)
        if (in_array($rolNavDash, [1, 2], true)):
      ?>
      <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'general']) ?>" class="nav-item <?= $navActive('Dashboard', 'general') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
        Panel general
      </a>
      <?php endif; ?>
      <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Empresas') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h2v2H7V5zm0 4h2v2H7V9zm0 4h2v2H7v-2zm4-8h2v2h-2V5zm0 4h2v2h-2V9zm0 4h2v2h-2v-2z"/></svg>
        Documentación empresa
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Vehiculos') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H11a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7h-.5a1 1 0 00-1 1v6.05A2.5 2.5 0 0115 16.5h.5a1 1 0 001-1V9a2 2 0 00-2-2h0z"/></svg>
        Vehículos
        <?php if ($vehiculosAlerta > 0): ?><span class="nav-badge warn"><?= $vehiculosAlerta ?></span><?php endif; ?>
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Cumplimiento', 'index') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        Cumplimiento
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos']) ?>" class="nav-item <?= $navActive('Cumplimiento', 'materialesPeligrosos') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Materiales peligrosos
      </a>
      <a href="<?= $this->Url->build(['controller' => 'DocumentosAdicionales', 'action' => 'index']) ?>" class="nav-item <?= $navActive('DocumentosAdicionales') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
        Documentos adicionales
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Alertas', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Alertas') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
        Alertas
        <?php if ($alertasTotal > 0): ?><span class="nav-badge warn"><?= $alertasTotal ?></span><?php endif; ?>
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-label">Vehículos y personal</div>
      <a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Operadores') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
        Operadores
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Verificaciones', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Verificaciones') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm1-5a1 1 0 100 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
        Historial de verificaciones
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Bajas', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Bajas') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Bajas vehiculares
      </a>
    </div>
    <div class="nav-group nav-group-extra<?= $esModuloExtra ? ' nav-group-extra--open' : '' ?>">
      <div class="nav-group-label">Extras · Carta Porte / CFDI</div>
      <p class="nav-group-hint">Facturación y complementos de transporte (opcional)</p>
      <a href="<?= $this->Url->build(['controller' => 'Comprobantes', 'action' => 'generar']) ?>" class="nav-item nav-item-extra <?= $navActive('Comprobantes', 'generar') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/></svg>
        Generar Carta Porte
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Comprobantes', 'action' => 'index']) ?>" class="nav-item nav-item-extra <?= $navActive('Comprobantes', 'index') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M6 2a2 2 0 00-2 2v14l3-2 3 2 3-2 3 2V4a2 2 0 00-2-2H6zm1 4h6a1 1 0 110 2H7a1 1 0 110-2zm0 4h6a1 1 0 110 2H7a1 1 0 110-2z"/></svg>
        Comprobantes emitidos
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'index']) ?>" class="nav-item nav-item-extra <?= $navActive('Ubicaciones') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 6.05a7 7 0 119.9 9.9L10 20l-4.95-4.05a7 7 0 010-9.9zM10 4a3 3 0 100 6 3 3 0 000-6z" clip-rule="evenodd"/></svg>
        Ubicaciones
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Clientes', 'action' => 'index']) ?>" class="nav-item nav-item-extra <?= $navActive('Clientes') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a4 4 0 100 8 4 4 0 000-8zM4 18a6 6 0 0112 0H4z"/></svg>
        Clientes
      </a>
      <a href="/facturacion/servicios" class="nav-item nav-item-extra <?= $navActive('Servicios') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M4 5a2 2 0 012-2h3a1 1 0 010 2H6v10h8V5h-3a1 1 0 110-2h3a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/></svg>
        Productos y servicios
      </a>
      <a href="<?= $this->Url->build(['controller' => 'CatalogoMercancias', 'action' => 'index']) ?>" class="nav-item nav-item-extra <?= $navActive('CatalogoMercancias') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"/></svg>
        Catálogo de mercancías
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-label">Sistema</div>
      <?php
        $rolLayout = is_array($authUserLayout ?? null) ? (int)($authUserLayout['rol_id'] ?? 0) : 0;
        if (in_array($rolLayout, [1, 2], true)):
      ?>
      <a href="<?= $this->Url->build(['controller' => 'Reportes', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Reportes') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
        Reportes
      </a>
      <a href="<?= $this->Url->build(['controller' => 'ReglasAlertas', 'action' => 'index']) ?>" class="nav-item <?= $navActive('ReglasAlertas') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
        Reglas de alertas
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Auditoria', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Auditoria') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
        Auditoría
      </a>
      <a href="<?= $this->Url->build(['controller' => 'TiposDocumento', 'action' => 'index']) ?>" class="nav-item <?= $navActive('TiposDocumento') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
        Tipos de documento
      </a>
      <a href="<?= $this->Url->build(['controller' => 'Usuarios', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Usuarios') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
        Accesos
      </a>
      <?php if ($rolLayout === 1): ?>
      <a href="<?= $this->Url->build(['controller' => 'Cuentas', 'action' => 'index']) ?>" class="nav-item <?= $navActive('Cuentas') ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
        Cuentas
      </a>
      <?php endif; ?>
      <?php endif; ?>
      <?= $this->Form->postLink(
        '<svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h6a1 1 0 110 2H5v10h5a1 1 0 110 2H4a1 1 0 01-1-1V4zm10.293 4.293a1 1 0 011.414 0l3 3a.997.997 0 01.083.094l.007.01a.995.995 0 01-.09 1.32l-3 3a1 1 0 01-1.414-1.414L14.586 13H8a1 1 0 110-2h6.586l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>Salir',
        ['controller' => 'Sesiones', 'action' => 'salir'],
        [
          'class' => 'nav-item',
          'escapeTitle' => false,
          'confirm' => '¿Salir del sistema?',
        ]
      ) ?>
    </div>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= $this->Url->build('/manual-usuario.html') ?>" class="nav-item" style="margin-bottom:6px;font-size:12px" target="_blank" rel="noopener">Manual de usuario</a>
    v1.0 · SAF © 2026
  </div>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="main">
  <div class="topbar">
    <button type="button" class="btn btn-ghost btn-icon sidebar-toggle" id="sidebar-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="saf-sidebar">
      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
    </button>
    <div class="topbar-title"><?= h($topbarTitle) ?></div>
    <div class="topbar-actions">
      <?= $this->Form->create(null, [
          'url' => ['controller' => 'Busqueda', 'action' => 'index'],
          'type' => 'get',
          'class' => 'search-wrap',
          'id' => 'saf-topbar-search',
      ]) ?>
        <svg class="search-icon" width="13" height="13" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="search" name="q" value="<?= h($this->request->getQuery('q') ?? '') ?>" placeholder="Buscar empresa, vehículo, operador, archivo…" autocomplete="off" minlength="2">
      <?= $this->Form->end() ?>
      <div
        class="topbar-notif-wrap"
        id="saf-notif-wrap"
        data-panel-url="<?= h($this->Url->build(['controller' => 'Notificaciones', 'action' => 'panel'])) ?>"
        data-marcar-url="<?= h($this->Url->build(['controller' => 'Notificaciones', 'action' => 'marcarLeida'])) ?>"
        data-marcar-todas-url="<?= h($this->Url->build(['controller' => 'Notificaciones', 'action' => 'marcarTodas'])) ?>"
      >
        <button type="button" class="btn btn-ghost btn-icon topbar-notif-btn" id="saf-notif-btn" title="Notificaciones" aria-expanded="false" aria-haspopup="true">
          <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
          <span class="topbar-notif-badge hidden" id="saf-notif-count" aria-live="polite">0</span>
        </button>
        <div class="topbar-notif-panel hidden" id="saf-notif-panel" role="menu" aria-label="Notificaciones">
          <div class="topbar-notif-head">
            <span id="saf-notif-head-text">Notificaciones</span>
            <button type="button" class="topbar-notif-mark-all" id="saf-notif-mark-all">Marcar todas como leídas</button>
          </div>
          <div id="saf-notif-items">
            <div class="topbar-notif-empty">Cargando…</div>
          </div>
          <a class="topbar-notif-link" href="<?= $this->Url->build(['controller' => 'Alertas', 'action' => 'index']) ?>">Ver todas las alertas →</a>
        </div>
      </div>
      <div style="width:30px;height:30px;border-radius:50%;background:var(--navy);display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:700;font-family:'Syne',sans-serif;cursor:pointer;border:2px solid var(--teal)">A</div>
    </div>
  </div>

  <div class="content">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
  </div>
</div>

</div>
<?php if (!empty($mostrarModalDocumentosVencidos) && !empty($documentosVencidosModal)): ?>
  <?= $this->element('modal_documentos_vencidos', ['documentosVencidosModal' => $documentosVencidosModal]) ?>
<?php endif; ?>
<script src="<?= h($this->Url->build('/js/saf.js')) ?>?v=20260806a"></script>
</body>
</html>
