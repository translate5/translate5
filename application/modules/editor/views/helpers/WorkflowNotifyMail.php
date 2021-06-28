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
 * @package portal
 * @version 2.0
 *
 */
/**
 * Utility functions usable in workflow notification E-Mails.
 */
class View_Helper_WorkflowNotifyMail extends Zend_View_Helper_Abstract {
    public function workflowNotifyMail() {
        return $this;
    }
    
    /**
     * render the HTML user list table
     * @param array $users
     * @return string
     */
    public function renderUserList(array $users) {
        // anonymize users?
        $task = $this->view->task;
        
        $notifyConfig = $task->getConfig()->runtimeOptions->editor->notification;
        $columns = $notifyConfig->userListColumns->toArray();
        
        $receiverLocale=$this->view->receiver->locale ?? null;
        
        /* @var $task editor_Models_Task */
        $taskGuid = $task->getTaskGuid();
        
        $rolesOfReceiver = is_string($this->view->receiver->roles) ? explode(',', $this->view->receiver->roles) : $this->view->receiver->roles;
        if ($task->anonymizeUsers(true, $rolesOfReceiver)) {
            // = anonymize $users for task without taking the addressed user into account
            // (the receiver of the mail might not be the currently authenticated user)
            $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            /* @var $workflowAnonymize editor_Workflow_Anonymize */
            foreach($users as &$user) {
                $user = $workflowAnonymize->anonymizeUserdata($taskGuid, $user['userGuid'], $user);
            }
        }
        //reset the tmp user variable
        unset($user);
        
        $firstUser = reset($users);
        $hasState = !empty($firstUser) && array_key_exists('state', $firstUser);
        $hasRole = !empty($firstUser) && array_key_exists('role', $firstUser);
        $t = $this->view->translate;
        $result = array('<table cellpadding="4">');
        $th = '<th align="left">';
        $result[] = '<tr>';
        
        $colHeads = ['surName' => 'Nachname', 'firstName' => 'Vorname', 'email' => 'E-Mail Adresse', 'role' => 'Rolle', 'state' => 'Status', 'deadlineDate'=>'Deadline Datum'];
        
        if(!$hasRole) {
            //remove 'role' from $columns;
            $columns = array_diff($columns, ['role']);
        }
        if(!$hasState) {
            //remove 'state' from $columns;
            $columns = array_diff($columns, ['state']);
        }
        
        foreach($columns as $col) {
            $result[] = $th.$t->_($colHeads[$col]).'</th>';
        }
        $result[] = '</tr>';
        
        //fields to be translated for the receiver
        $translateFieldValues=['state','role'];
        $t = $this->view->translate;
        foreach($users as $user) {
            $result[] = "\n".'<tr>';
            foreach($columns as $col) {
                $val=$user[$col] ?? '';
                if(in_array($col,$translateFieldValues) && !empty($val)){
                    //translate the value for the receiver locale
                    $val=$t->_($user[$col],$receiverLocale);
                }
                $result[] = '<td>'.$val.'</td>';
            }
            $result[] = '</tr>';
        }
        $result[] = '</table>';
        return join('', $result);
    }
    
    /**
     * returns an array with translated language names used in the given task
     * The result is ready to be used in mail templates
     *
     * @param editor_Models_Task $task
     * @return array
     */
    public function getTaskLanguages(editor_Models_Task $task) {
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */

        $params = [];
        
        try {
            $lang->load($task->getSourceLang());
            $params['sourceLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
        }
        catch (Exception $e) {
            $params['sourceLanguageTranslated'] = 'unknown';
        }
        
        try {
            $lang->load($task->getTargetLang());
            $params['targetLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
        }
        catch (Exception $e) {
            $params['targetLanguageTranslated'] = 'unknown';
        }

        $relais = $task->getRelaisLang();
        if(!empty($relais)) {
            try {
                $lang->load($task->getRelaisLang());
                $params['relaisLanguageTranslated'] = $this->view->translate->_($lang->getLangName());
            }
            catch (Exception $e) {
                $params['relaisLanguageTranslated'] = 'unknown';
            }
            $params['relaisLanguageFragment'] = $this->view->translate->_('<b>Relaissprache:</b> {relaisLanguageTranslated}<br />');
        }
        else {
            $params['relaisLanguageFragment'] = '';
        }
        
        return $params;
    }
    
    /***
     * returns a date in the locale of the receiver
     * @param string/int $date
     * @param boolean $isDateTime: set to true to include the time
     * @return string
     */
    public function dateFormat($date,$isDateTime=false) {
        if(empty($this->view->receiver->locale)) {
            $locale = $this->view->config->runtimeOptions->translation->fallbackLocale;
        }
        else {
            $locale = $this->view->receiver->locale;
        }
        $format = $isDateTime ? Zend_Locale_Format::getDateTimeFormat($locale) : Zend_Locale_Format::getDateFormat($locale);
        $date = new Zend_Date($date,Zend_Date::ISO_8601);
        return $date->toString($format);
    }
}