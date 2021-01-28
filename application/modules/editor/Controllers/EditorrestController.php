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
 * All Controllers which need a taskGuid in the session for further processing needs this controller as parent!
 */
abstract class editor_Controllers_EditorrestController extends ZfExtended_RestController {

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::initRestControllerSpecific()
     */
    protected function initRestControllerSpecific() {
        parent::initRestControllerSpecific();
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->session = new Zend_Session_Namespace();
    }
    
    /**
     * Editorrest controller checks for a valid taskGuid in the session before proceeding
     * {@inheritDoc}
     * @see ZfExtended_RestController::preDispatch()
     */
    public function preDispatch(){
        $guid = new ZfExtended_Validate_Guid();
        if(!$guid->isValid($this->session->taskGuid)) {
            $e = new ZfExtended_NoAccessException();
            $e->setMessage("Sie haben keine Aufgabe (mehr) geöffnet. Eventuell wurde die Aufgabe in einem anderen Fenster geschlossen.", true);
            $e->setLogging(false); //TODO loglevel info
            throw $e;
        }
        parent::preDispatch();
    }
}
