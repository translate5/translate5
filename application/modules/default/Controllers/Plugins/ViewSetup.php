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

/**#@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */

/**
 * Plugin to load custom css
 */
class Controllers_Plugins_ViewSetup extends Zend_Controller_Plugin_Abstract
{
    /**
     * Loads the custom css into each controller
     *
     * @param  Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $renderer = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $css = str_replace('//', '/', APPLICATION_RUNDIR.'/'.$config->runtimeOptions->server->pathToCSS);
        $renderer->view->headLink()->appendStylesheet($css);
    }
}
