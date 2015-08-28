<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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