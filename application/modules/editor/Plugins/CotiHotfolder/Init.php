<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Cronjob\CronEventTrigger;
use MittagQI\Translate5\DbConfig\ActionsEventHandler;
use MittagQI\Translate5\Plugins\CotiHotfolder\CotiHotfolder;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemFactory;
use Translate5\MaintenanceCli\Command\FilesystemExternalCheckCommand;

class editor_Plugins_CotiHotfolder_Init extends ZfExtended_Plugin_Abstract
{
    public const DEFAULT_PM_CONFIG = 'runtimeOptions.plugins.CotiHotfolder.defaultPM';

    protected static bool $enabledByDefault = true;

    protected static bool $activateForTests = true;

    protected static string $description = 'Provides COTI-Level 2 Support';

    public function init(): void
    {
        $this->addRoute();
        $this->eventManager->attach(
            CronEventTrigger::class,
            CronEventTrigger::PERIODICAL,
            [$this, 'checkFolderForUpdates']
        );
        $this->eventManager->attach(
            CronEventTrigger::class,
            CronEventTrigger::DAILY,
            [$this, 'cleanupDeletedProjects']
        );
        $eventHandler = new ActionsEventHandler();
        $this->eventManager->attach(
            editor_ConfigController::class,
            'afterIndexAction',
            $eventHandler->addDefaultPMUsersOnIndexAction(self::DEFAULT_PM_CONFIG, [
                Roles::PM,
                Roles::CLIENTPM,
                Roles::PMLIGHT,
            ])
        );
        $this->eventManager->attach(
            editor_ConfigController::class,
            'afterPutAction',
            $eventHandler->addDefaultPMUsersOnPutAction(self::DEFAULT_PM_CONFIG, [
                Roles::PM,
                Roles::CLIENTPM,
                Roles::PMLIGHT,
            ])
        );
        $this->eventManager->attach(
            FilesystemExternalCheckCommand::class,
            FilesystemExternalCheckCommand::EVENT_CHECK,
            [$this, 'handleConfigCheck']
        );
        $this->eventManager->attach(
            ZfExtended_TemplateBasedMail::class,
            'afterMailViewInit',
            [$this, 'addMailTemplatesScriptPath']
        );
    }

    public function addMailTemplatesScriptPath(Zend_EventManager_Event $event)
    {
        /** @var Zend_View $view */
        $view = $event->getParam('view');
        $view->addScriptPath($this->getPluginPath() . '/mailTemplates/');
    }

    public function handleConfigCheck(): array
    {
        return [$this->pluginName, FilesystemFactory::class, FilesystemFactory::FILESYSTEM_CONFIG_NAME];
    }

    public function checkFolderForUpdates(): void
    {
        CotiHotfolder::create()->checkFilesystems();
    }

    public function cleanupDeletedProjects(): void
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query('DELETE u FROM LEK_coti_upload u LEFT JOIN LEK_coti_project_upload_assoc pua
ON u.id = pua.upload_id WHERE pua.upload_id IS NULL');
    }

    private function addRoute(): void
    {
        $this->addController('HotfolderController');

        $router = Zend_Registry::get('frontController')->getRouter();

        $router->addRoute(
            'plugins_cotihotfolder_hotfolder_force',
            new ZfExtended_Controller_RestFakeRoute(
                'editor/coti-hotfolder/force-check',
                [
                    'module' => 'editor',
                    'controller' => 'plugins_cotihotfolder_hotfolder',
                    'action' => 'force',
                ]
            )
        );
    }
}
