<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/** #@+
 * @author Marc Mittag
 * @package translate5
 * @version 0.7
 *
 */
require_once'ControllerMixIns.php';
/**
 * Stellt Methoden bereit, die translate5 grundsätzlich als Stand Alone-Anwendung verfügbar machen
 */
class IndexController extends ZfExtended_Controllers_Action {
    use ControllerMixIns;
    /**
     * @var boolean projectImported Projekt ist importiert
     */
    protected $projectImported;

    public function init(){
        parent::init();
        $this->setProjectImported();
        $this->view->languageSelector();
    }

    /**
     * registers the needed data for editor usage in session
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $userName
     */
    protected function registerSessionData(string $taskGuid, string $userGuid, string $userName) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $task->registerInSession();
        $sessionUser = new Zend_Session_Namespace('user');
        $sessionUser->userGuid = $userGuid;
        $sessionUser->userName = $userName;
    }

    protected function setProjectImported(){
        $db = Zend_Registry::get('db');
        $count = $db->fetchOne( 'SELECT COUNT(*) AS count FROM LEK_files' );
        $this->projectImported = ($count == 0)?false:true;
        $this->view->projectImported = $this->projectImported;
    }
    protected function getUri(){
        $prot = (empty($_SERVER['HTTPS'])) ? 'http://' : 'https://';
        return $prot . $_SERVER['SERVER_NAME'] . preg_replace('"/$"', '', $_SERVER['REQUEST_URI']);
    }

    protected function getGuid(){
        //from http://www.php.net/manual/en/function.uniqid.php#94959
        return sprintf( '{%04x%04x-%04x-%04x-%04x-%04x%04x%04x}',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function impressumAction(){
        $this->view->impressum = true;
    }
}