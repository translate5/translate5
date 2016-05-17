<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Initial Class of Plugin "TranslationMemory"
 * 
 * @FIXME Hint: class must be named NOT Bootstrap, otherwise we will get a strange Zend Error
 */
class editor_Plugins_TmMtIntegration_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array('Editor.plugins.TmMtIntegration.controller.Controller',
    									   'Editor.plugins.TmMtIntegration.controller.TmOverviewController');
    
    public function init() {
        $f = Zend_Registry::get('frontController');
        $f->addControllerDirectory(APPLICATION_PATH.'/'.$this->getPluginPath().'/Controllers', '_plugins');
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
        		'editor' => array('plugins_tmmtintegration_taskassoc'),
        ));
        $f->getRouter()->addRoute('plugins_tmmtintegration_taskassoc', $restRoute);
        
         //im folgenden zwei testcontroller
        $rest = new Zend_Controller_Router_Route(
            'editor/js/app-localized.jsx',
            array(
                'module' => 'editor',
                'controller' => 'plugins_tmmtintegration_resource',
                'action' => 'demo',
            ));
        $f->getRouter()->addRoute('plugins_tmmtintegration_test', $rest);
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
            'editor' => array('plugins_tmmtintegration_resource'),
        ));
        $f->getRouter()->addRoute('plugins_tmmtintegration_rest', $restRoute);
    }
}
