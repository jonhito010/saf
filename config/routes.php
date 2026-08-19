<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * Here, we are connecting '/' (base path) to a controller called 'Pages',
         * its action called 'display', and we pass a param to select the view file
         * to use (in this case, templates/Pages/home.php)...
         */
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->connect('/dashboard/general', ['controller' => 'Dashboard', 'action' => 'general']);
        $builder->connect('/empresa', ['controller' => 'Empresas', 'action' => 'index']);
        $builder->connect('/ubicaciones', ['controller' => 'Ubicaciones', 'action' => 'index']);
        $builder->connect('/vehiculos', ['controller' => 'Vehiculos', 'action' => 'index']);
        $builder->connect('/vehiculos/{id}', ['controller' => 'Vehiculos', 'action' => 'view'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/operadores', ['controller' => 'Operadores', 'action' => 'index']);
        $builder->connect('/operadores/cambiar-estatus/{id}', ['controller' => 'Operadores', 'action' => 'cambiarEstatus'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/bajas', ['controller' => 'Bajas', 'action' => 'index']);
        $builder->connect('/cumplimiento', ['controller' => 'Cumplimiento', 'action' => 'index']);
        $builder->connect('/cumplimiento/materiales-peligrosos', ['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos']);
        $builder->connect('/documentos-adicionales', ['controller' => 'DocumentosAdicionales', 'action' => 'index']);
        $builder->connect('/alertas', ['controller' => 'Alertas', 'action' => 'index']);
        $builder->connect('/alertas/ir/{id}', ['controller' => 'Alertas', 'action' => 'ir'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/buscar', ['controller' => 'Busqueda', 'action' => 'index']);
        $builder->connect('/accesos', ['controller' => 'Usuarios', 'action' => 'index']);
        $builder->connect('/accesos/nuevo', ['controller' => 'Usuarios', 'action' => 'add']);
        $builder->connect('/accesos/{id}', ['controller' => 'Usuarios', 'action' => 'edit'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/accesos/suspender/{id}', ['controller' => 'Usuarios', 'action' => 'suspender'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/accesos/activar/{id}', ['controller' => 'Usuarios', 'action' => 'activar'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/usuarios/suspender/{id}', ['controller' => 'Usuarios', 'action' => 'suspender'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/usuarios/activar/{id}', ['controller' => 'Usuarios', 'action' => 'activar'], ['id' => '\d+', 'pass' => ['id']]);

        // Reportes, auditoría, alertas, notificaciones, cuentas
        $builder->connect('/reportes', ['controller' => 'Reportes', 'action' => 'index']);
        $builder->connect('/reportes/exportar-csv', ['controller' => 'Reportes', 'action' => 'exportarCsv']);
        $builder->connect('/reportes/exportar-xlsx', ['controller' => 'Reportes', 'action' => 'exportarXlsx']);
        $builder->connect('/reportes/exportar-completo', ['controller' => 'Reportes', 'action' => 'exportarCompleto']);
        $builder->connect('/auditoria', ['controller' => 'Auditoria', 'action' => 'index']);
        $builder->connect('/auditoria/cambios', ['controller' => 'Auditoria', 'action' => 'cambios']);
        $builder->connect('/tipos-documento', ['controller' => 'TiposDocumento', 'action' => 'index']);
        $builder->connect('/tipos-documento/importar', ['controller' => 'TiposDocumento', 'action' => 'importar']);
        $builder->connect('/reglas-alertas', ['controller' => 'ReglasAlertas', 'action' => 'index']);
        $builder->connect('/notificaciones/panel', ['controller' => 'Notificaciones', 'action' => 'panel']);
        $builder->connect('/notificaciones/marcar-leida', ['controller' => 'Notificaciones', 'action' => 'marcarLeida']);
        $builder->connect('/notificaciones/marcar-todas', ['controller' => 'Notificaciones', 'action' => 'marcarTodas']);
        $builder->connect('/cuentas', ['controller' => 'Cuentas', 'action' => 'index']);
        $builder->connect('/cuentas/crear', ['controller' => 'Cuentas', 'action' => 'crear']);
        $builder->connect('/cuentas/suspender/{id}', ['controller' => 'Cuentas', 'action' => 'suspender'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/cuentas/activar/{id}', ['controller' => 'Cuentas', 'action' => 'activar'], ['id' => '\d+', 'pass' => ['id']]);
        $builder->connect('/verificaciones', ['controller' => 'Verificaciones', 'action' => 'index']);
        $builder->connect('/verificaciones/nueva', ['controller' => 'Verificaciones', 'action' => 'add']);
        $builder->connect('/usuarios', ['controller' => 'Usuarios', 'action' => 'index']);
        $builder->connect('/usuarios/nuevo', ['controller' => 'Usuarios', 'action' => 'add']);
        $builder->connect('/usuarios/{id}', ['controller' => 'Usuarios', 'action' => 'edit'], ['id' => '\d+', 'pass' => ['id']]);

        // Facturación
        $builder->connect('/facturacion', ['controller' => 'Comprobantes', 'action' => 'index']);
        $builder->connect('/facturacion/nueva', ['controller' => 'Comprobantes', 'action' => 'generar']);
        $builder->connect('/facturacion/importar-xml', ['controller' => 'Comprobantes', 'action' => 'importarXml']);
        $builder->connect('/facturacion/clientes', ['controller' => 'Clientes', 'action' => 'index']);
        $builder->connect('/facturacion/servicios', ['controller' => 'Servicios', 'action' => 'index']);
        $builder->connect('/facturacion/mercancias', ['controller' => 'CatalogoMercancias', 'action' => 'index']);

        // Vista previa PDF temporal (debe ir antes de los fallbacks)
        $builder->connect(
            '/comprobantes/tmp-pdf/{token}',
            ['controller' => 'Comprobantes', 'action' => 'tmpPdf'],
            ['token' => '[a-zA-Z0-9_.]+', 'pass' => ['token']]
        );

        // Salir del sistema (logout)
        $builder->connect('/salir', ['controller' => 'Sesiones', 'action' => 'salir']);
        $builder->connect('/login', ['controller' => 'Sesiones', 'action' => 'login']);
        $builder->connect('/registro', ['controller' => 'Sesiones', 'action' => 'registro']);
        $builder->connect('/archivos/ver', ['controller' => 'Archivos', 'action' => 'ver']);
        $builder->connect('/archivos/descargar', ['controller' => 'Archivos', 'action' => 'descargar']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
