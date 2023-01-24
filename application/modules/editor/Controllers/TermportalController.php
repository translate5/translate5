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

class Editor_TermportalController extends ZfExtended_Controllers_Action
{
    public function indexAction()
    {
        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        $plugin = $pluginmanager->get('TermPortal');
        if(empty($plugin)) {
            //load default screen to show plugin disable message
            return;
        }

        // Get config's runtimeOptions
        $rop = Zend_Registry::get('config')->runtimeOptions;

        // Get enableJsLogger param
        $this->view->enableJsLogger = $rop->debug && $rop->debug->enableJsLogger;

        $this->view->cssFiles = [
                'itranslate' => $this->_helper->ClientSpecific->getCss('instanttranslate') ?? false,
                'termportal' => $this->_helper->ClientSpecific->getCss('termportal') ?? false,
        ];

        // Setup basic info
        $Editor = [
            'data' => [
                'logoutOnWindowClose' => $rop->logoutOnWindowClose,
                'pathToRunDir' => APPLICATION_RUNDIR
            ]
        ];

        // If it is turned On
        if ($this->view->enableJsLogger) {

            // Get current user data
            $user = (new Zend_Session_Namespace('user'))->data;

            // Assign view params, required for RootCause usage
            $this->view->assign([
                'appVersion' => ZfExtended_Utils::getAppVersion(),
                'extJsVersion' => '7.0.0.168 GPL'
            ]);

            // Append app info
            $Editor['data']['app'] = [
                'controllers' => [],
                'user' => [
                    'login' => $user->login,
                    'email' => $user->email,
                    'userGuid' => $user->userGuid
                ]
            ];
        }

        // Assign editor params
        $this->view->assign(['Editor' => $Editor]);

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->addScriptPath(APPLICATION_ROOT.'/application/modules/editor/Plugins/TermPortal/public/resources/');
        $which = ZfExtended_Utils::getAppVersion() == ZfExtended_Utils::VERSION_DEVELOPMENT
            ? 'build/production/TermPortal/index.php'
            : 'index.php';
        echo $this->view->render($which);
    }

    public function customheaderAction(){
        $this->view->layout()->disableLayout();
    }
}
