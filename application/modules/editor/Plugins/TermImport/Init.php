<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
use MittagQI\Translate5\Cronjob\CronEventTrigger;
use MittagQI\Translate5\Plugins\TermImport\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;
use MittagQI\Translate5\Plugins\TermImport\TermImport;
use Translate5\MaintenanceCli\Command\FilesystemExternalCheckCommand;

/***
 * This plug-in is used for automatically exports TBX files from the filesystem and Across and imports them to translate5.
 * In the config folder there are custom cinfigs for each tbx import type(filesystem and crossapi)
 * More info: https://confluence.translate5.net/display/TAD/Plug-In+TermImport%3A+TermImport
 */
class editor_Plugins_TermImport_Init extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Provides a term import on file level';

    protected static bool $enabledByDefault = true;

    /**
     * @var array
     */
    protected $frontendControllers = [];

    public function init()
    {
        $this->addController('TermImportController');
        $this->initRoutes();
        $this->eventManager->attach(
            CronEventTrigger::class,
            CronEventTrigger::PERIODICAL,
            [$this, 'checkFilesystemsForUpdates']
        );

        $this->eventManager->attach(
            FilesystemExternalCheckCommand::class,
            FilesystemExternalCheckCommand::EVENT_CHECK,
            [$this, 'handleConfigCheck']
        );
    }

    public function handleConfigCheck(): array
    {
        return [$this->pluginName, FilesystemFactory::class, FilesystemFactory::FILESYSTEM_CONFIG_NAME];
    }

    /**
     * defines all URL routes of this plug-in
     * @uses editor_Plugins_TermImport_TermImportController::crossapiAction()
     * @uses editor_Plugins_TermImport_TermImportController::filesystemAction()
     */
    protected function initRoutes()
    {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();

        $restRoute = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_termimport_termimport',
            ],
        ]);
        $r->addRoute('plugins_termimport_restdefault', $restRoute);

        $filesystemRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_termimport_termimport/filesystem',
            [
                'module' => 'editor',
                'controller' => 'plugins_termimport_termimport',
                'action' => 'filesystem',
            ]
        );
        $r->addRoute('plugins_termimport_filesystem', $filesystemRoute);

        $crossapiRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_termimport_termimport/crossapi',
            [
                'module' => 'editor',
                'controller' => 'plugins_termimport_termimport',
                'action' => 'crossapi',
            ]
        );
        $r->addRoute('plugins_termimport_crossapi', $crossapiRoute);

        $r->addRoute(
            'plugins_termimport_force',
            new ZfExtended_Controller_RestFakeRoute('editor/termimport/force-check', [
                'module' => 'editor',
                'controller' => 'plugins_termimport_termimport',
                'action' => 'force',
            ])
        );
    }

    /**
     * @throws MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException
     */
    public function checkFilesystemsForUpdates(): void
    {
        (new TermImport(new FilesystemFactory(new LoggerService())))->checkFilesystems();
    }
}
