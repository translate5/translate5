<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

use MittagQI\Translate5\Applet\AppletAbstract;
use MittagQI\Translate5\Applet\Dispatcher;

class editor_Plugins_TMMaintenance_Init extends ZfExtended_Plugin_Abstract
{
    protected static $description = 'Provides a functionality of managing t5memory TM';

    /**
     * {@inheritdoc}
     */
    protected $frontendControllers = array(
        'pluginTMMaintenanceTMMaintenance' => 'Editor.plugins.TMMaintenance.app.controller.TMMaintenance'
    );

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->addController('TMMaintenanceController');
        $this->addController('ApiController');
        $this->initApplet();
        $this->initRoutes();
    }

    private function initApplet()
    {
        // Register the plugin as an applet
        Dispatcher::getInstance()->registerApplet('tmmaintenance', new class extends AppletAbstract {
            protected string $urlPathPart = '/editor/tmmaintenance';
            protected string $initialPage = 'tmMaintenance';
        });
    }

    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes()
    {
        /* @var $frontController Zend_Controller_Front */
        $frontController = Zend_Registry::get('frontController');
        $router = $frontController->getRouter();

        if (null === $router) {
            // TODO do something
            throw new \Error('No router');
        }

        $restRoute = new Zend_Rest_Route(
            $frontController,
            [],
            [
                'editor' => ['plugins_tmmaintenance_api'],
            ]
        );
        $router->addRoute('plugins_tmmaintenance_restdefault', $restRoute);

        $route = new ZfExtended_Controller_RestLikeRoute('editor/plugins_tmmaintenance_api/locale/list', [
            'module' => 'editor',
            'controller' => 'plugins_tmmaintenance_api',
            'action' => 'locales'
        ]);
        $router->addRoute('plugins_tmmaintenance_locales', $route);

        $route = new ZfExtended_Controller_RestLikeRoute('editor/plugins_tmmaintenance_api/tm/list', [
            'module' => 'editor',
            'controller' => 'plugins_tmmaintenance_api',
            'action' => 'tms'
        ]);
        $router->addRoute('plugins_tmmaintenance_tms', $route);

        $route = new ZfExtended_Controller_RestLikeRoute('editor/plugins_tmmaintenance_api/segment/search', [
            'module' => 'editor',
            'controller' => 'plugins_tmmaintenance_api',
            'action' => 'search'
        ]);
        $router->addRoute('plugins_tmmaintenance_segments', $route);
    }
}
