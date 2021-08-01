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

/**
 * Use Okapi via Tikal
 */
class editor_Plugins_Okapi_InitTikal extends ZfExtended_Plugin_Abstract {
    
    public function init() {
        throw new Exception("Not tested this plugin since refactored the file import!");
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'afterDirectoryParsing', array($this, 'handleBeforeImport'));
    }
    
    public function handleBeforeImport(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        
        $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
        /* @var $fileFilter editor_Models_File_FilterManager */
        foreach($params['filelist'] as $fileId => $relName) {
            $fileFilter->addFilter($fileFilter::TYPE_IMPORT, $params['task']->getTaskGuid(), $fileId, 'editor_Plugins_Okapi_Tikal_Filter');
        }
    }
}