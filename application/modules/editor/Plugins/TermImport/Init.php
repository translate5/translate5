<?php
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/***
 * This plug-in is used for automatically exports TBX files from the filesystem and Across and imports them to translate5.
 * In the config folder there are custom cinfigs for each tbx import type(filesystem and crossapi)
 * More info: https://confluence.translate5.net/display/TAD/Plug-In+TermImport%3A+TermImport
 */
class editor_Plugins_TermImport_Init extends ZfExtended_Plugin_Abstract {
    
    protected static $description = 'Provides a term import on file level';
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
    );
    
    
    public function init() {
        $this->addController('TermImportController');
        $this->initRoutes();
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_termimport_termimport',
                ),
        ));
        $r->addRoute('plugins_termimport_restdefault', $restRoute);
        
        
        $filesystemRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_termimport_termimport/filesystem',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_termimport_termimport',
                        'action' => 'filesystem'
                ));
        $r->addRoute('plugins_termimport_filesystem', $filesystemRoute);
        
        
        $crossapiRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_termimport_termimport/crossapi',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_termimport_termimport',
                        'action' => 'crossapi'
                ));
        $r->addRoute('plugins_termimport_crossapi', $crossapiRoute);
    }
}
