<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */

/**
 * Plugin, das den View Translate5spezifisch initialisiert
 *
 */
class Controllers_Plugins_ViewSetup extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Controller_Action_Helper_ViewRenderer
     */
    protected  $_viewRenderer;
    
    /**
     * @var Zend_Session_Namespace
     */
    protected $_session;
    /**
     * @var string Pfad zum ext-Verzeichnis aus der application.ini
     */
    protected $_extDir;

    /**
     *  Richtet den View ein
     * - befüllt Klassenvariablen dieser Klasse
     * - Lädt das benötigte JS und CSS
     * - legt Zend_Translate in den View
     * 
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract
                                        $request)
    {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->_session = new Zend_Session_Namespace();
        $this->_extDir = $config->runtimeOptions->extJs->basepath;
        $this->_viewRenderer = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $this->loadCssFiles($config);
    }

    /**
     * adds the translate5 css path to the  layout
     */
    private function loadCssFiles($config) {
        $css = str_replace('//', '/', APPLICATION_RUNDIR.'/'.$config->runtimeOptions->server->pathToCSS);
        $this->_viewRenderer->view->headLink()->appendStylesheet($css);
    }
}
