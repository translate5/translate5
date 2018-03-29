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

/**
 * extends ZfExtended_RestController for editor-specific necessities
 */
abstract class editor_Controllers_EditorrestController extends ZfExtended_RestController {

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     * checks if current session taskguid matches to loaded segment taskguid
     * @throws ZfExtended_NotAuthenticatedException
     */
    public function init() {
        $this->session = new Zend_Session_Namespace();
        parent::init();
    }
    
    public function preDispatch(){
        $guid = new ZfExtended_Validate_Guid();
        if(!$guid->isValid($this->session->taskGuid)) {
            $e = new ZfExtended_NoAccessException();
            $e->setMessage("Sie haben keine Aufgabe (mehr) geöffnet. Eventuell wurde die Aufgabe in einem anderen Fenster geschlossen.", true);
            $e->setLogging(false); //TODO loglevel info
            throw $e;
        }
        $this->afterTaskGuidCheck();
        parent::preDispatch();
    }
    
    protected function afterTaskGuidCheck() {
        //do nothing, for overwriting
    }
}
