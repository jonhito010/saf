<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use App\Service\DocumentosVencidosService;
use App\Service\UsuarioEmpresaService;
use Cake\Event\EventInterface;
use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 * @extends \Cake\View\View<\App\View\AppView>
 */
class AppView extends View
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like adding helpers.
     *
     * e.g. `$this->addHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        if ($this->get('mostrarModalDocumentosVencidos')) {
            return;
        }

        $layoutName = $this->getLayout() ?? 'default';
        if ($layoutName !== 'default') {
            return;
        }

        $request = $this->getRequest();
        if ($request->getParam('controller') === 'Sesiones') {
            return;
        }

        $session = $request->getSession();
        if (!$session->read('Auth.mostrarModalVencidos')) {
            return;
        }

        $empresaIds = [];
        $authUser = $session->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);
        if (is_array($authUser) && !empty($authUser['id'])) {
            try {
                $svc = new UsuarioEmpresaService();
                $empresaIds = $svc->idsEmpresasAccesibles(
                    (int)$authUser['id'],
                    (int)($authUser['rol_id'] ?? 0),
                );
            } catch (\Throwable $e) {
                $empresaIds = [];
            }
        }

        try {
            $lista = (new DocumentosVencidosService())->listarParaModal($empresaIds);
        } catch (\Throwable $e) {
            $lista = [];
        }

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
}
