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

/** #@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */

trait ControllerMixIns  {

    public function __call($method, $args){
        $controller = $this->_request->getControllerName();
        $action = $this->_request->getActionName();
        $url = "/".$controller."/".$action;
        $config = Zend_Registry::get('config');
        $menu = $config->runtimeOptions->content->mainMenu;
        if(empty($menu)) {
            return;
        }
        $found = false;
        foreach ($menu as $item) {
            $item = (array)$item;
            $item = each($item);
            $found = ($item['key']===$_SERVER['REQUEST_URI'])?true:false;
            if($found){
                break;
            }
        }
        if(
                $_SERVER['REQUEST_URI'] === '/index/support-the-project' || 
                $_SERVER['REQUEST_URI'] === '/index/source' || 
                $_SERVER['REQUEST_URI'] === '/index/outstanding-features' || 
                
                $_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] ===  APPLICATION_RUNDIR.'/'|| $_SERVER['REQUEST_URI'] ===  APPLICATION_RUNDIR){
            $found = true;
        }
        if (!$found){
            throw new ZfExtended_NotFoundException();
        }
        $actionMethod = $action.'Action';
        $this->render($action);
    }
}

