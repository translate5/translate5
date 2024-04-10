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

/** #@+
 * @author Marc Mittag
 * @package translate5
 * @version 0.7
 */

use MittagQI\Translate5\Service\SystemCheck;

/**
 * Stellt Methoden bereit, die translate5 grundsätzlich als Stand Alone-Anwendung verfügbar machen
 * Since these shall be available externally, there is no CSRF protection active
 */
class IndexController extends ZfExtended_Controllers_Action
{
    /**
     * View object
     * @var Zend_View
     */
    public $view;

    public function indexAction(): void
    {
        require_once 'default/Controllers/helpers/BrowserDetection.php';

        // Internet Explorer is not supported anymore! redirect IE 11 or below users to a specific error page
        if (BrowserDetection::isInternetExplorer()) {
            header('Location: ' . APPLICATION_RUNDIR . '/index/internetexplorer');
            exit;
        }

        //the redirect to the editor module is done in the view script.
        // this default behaviour can then be overwritten in client-specific if needed
    }

    /**
     * Endpoint for T5 CLI system:check command to test the worker
     * URL configuration based on the serverId stored in memcache
     * @noinspection PhpUnused
     */
    public function testserverAction(): void
    {
        $id = Models_SystemRequirement_Modules_Configuration::MEMCACHE_ID;
        $memcache = new ZfExtended_Cache_MySQLMemoryBackend();
        echo $id . ' ';
        echo $memcache->load($id);
        exit;
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_MaintenanceException
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function monitoringAction(): void
    {
        $this->_helper->layout->disableLayout();

        $conf = Zend_Registry::get('config');
        $maintenance = ZfExtended_Factory::get(ZfExtended_Models_Installer_Maintenance::class);

        $monitoringConfig = $conf->runtimeOptions->maintenance->monitoring;
        $isBackup = str_contains($maintenance->status()->message, $monitoringConfig->backupMessage);

        $this->view->monitoringMessage = 'All Ok';

        //we consider a maximum runtime of maintenance for backup of 30 minutes as ok,
        // everything longer should trigger a monitoring error
        if ($isBackup ? $maintenance->isActive($monitoringConfig->backupAllowedSeconds) : $maintenance->isActive()) {
            $this->view->monitoringHttpCode = 503;
            $this->view->monitoringMessage = 'maintenance is enabled';

            return;
        }

        // system check - without services! → boil error if anything wrong
        $validator = new ZfExtended_Models_SystemRequirement_Validator(false);
        $results = $validator->validate(ignoredModules: [SystemCheck::CHECK_NAME]);
        foreach ($results as $oneResult) {
            if ($oneResult->hasError() || $oneResult->hasWarning()) {
                $this->view->monitoringHttpCode = 500;
                $this->view->monitoringMessage = 'system status has errors or warnings';

                return;
            }
        }
    }

    /**
     * Shows a simple info page to the user that IE 11 is not supported anymore
     * @noinspection PhpUnused
     */
    public function internetexplorerAction(): void
    {
        $this->renderScript('error/internetExplorer.phtml');
    }

    /**
     * @throws ZfExtended_Models_MaintenanceException
     */
    public function displayMaintenance()
    {
        //disable default maintenance check for monitoring endpoint
        if ($this->_request->getActionName() == 'monitoring') {
            return;
        }
        parent::displayMaintenance();
    }
}
