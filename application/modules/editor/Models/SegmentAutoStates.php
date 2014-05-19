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
/**
 * Segment Auto States Helper Class
 * This class contains all autoState definitions and all autoState transitions, available by api
 * 
 * FIXME nextReleaseThomas in den workflowordner verschieben und in Abhängigkeit vom defaultworkflow definieren
 */
class editor_Models_SegmentAutoStates {
    
    /**
     * "translated" / 'Übersetzt' ehemals 'Nicht lektoriert'
     * @var integer
     */
    const TRANSLATED = 0;
    
    /**
     * "reviewed" / 'Geprüft' ehemals 'Lektoriert'
     * @var integer
     */
    const REVIEWED = 1;
    
    /**
     * "autoreviewed" / 'Autogeprüft' ehemals 'Auto-Lektoriert'
     * @var integer
     */
    const REVIEWED_AUTO = 2;
    
    /**
     * "locked" / 'Gesperrt' => locking of 100% matches
     * @var integer
     */
    const BLOCKED = 3;
    
    /**
     * "untranslated" / "Nicht übersetzt" → kommt wie 0 initial aus dem Import
     * @var integer
     */
    const NOT_TRANSLATED = 4;
    
    /**
     * "reviewed untouched, auto-set" / "Geprüft" → Beim Abschließen eines Tasks werden alle "unberührten" Segmente auf diesen Status gesetzt.
     * @var integer
     */
    const REVIEWED_UNTOUCHED = 5;
    
    /**
     * "reviewed, untouched" / "Geprüft, unverändert" → Wenn das Segment geöffnet und dann gespeichert wurde ohne was zu verändern
     * @var integer
     */
    const REVIEWED_UNCHANGED = 6;
    
    /**
     * "autoreviewed, untouched" / "Autogeprüft, unverändert" → analog zu 6 bei den Wiederholungen
     * @var integer
     */
    const REVIEWED_UNCHANGED_AUTO = 7;
    
    /**
     * "translator reviewed" / "Übersetzer geprüft" → wenn ein Übersetzer das Segment verändert hat
     * @var integer
     */
    const REVIEWED_TRANSLATOR = 8;
    
    /**
     * "translator autoreviewed" / "Übersetzer autogeprüft" → wenn ein Übersetzer das Segment verändert hat
     * @var integer
     */
    const REVIEWED_TRANSLATOR_AUTO = 9;
    
    /**
     * reviewed by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM = 10;
    
    /**
     * reviewed through the repetition editor by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_AUTO = 11;
    
    /**
     * reviewed but unchanged by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_UNCHANGED = 12;
    
    /**
     * reviewed but unchanged through the repetition editor by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_UNCHANGED_AUTO = 13;
    
    /**
     * Internal state used to show segment is pending
     * @var integer
     */
    const PENDING = 999;
    
    protected $states = array(
        self::TRANSLATED => 'Übersetzt',
        self::NOT_TRANSLATED => 'Nicht übersetzt',
        self::REVIEWED => 'Lektoriert',
        self::REVIEWED_AUTO => 'Autolektoriert',
        self::BLOCKED => 'Gesperrt',
        self::REVIEWED_UNTOUCHED => 'Lektoriert, unberührt, auto-gesetzt',
        self::REVIEWED_UNCHANGED => 'Lektoriert, unverändert',
        self::REVIEWED_UNCHANGED_AUTO => 'Autolektoriert, unverändert',
        self::REVIEWED_TRANSLATOR => 'Übersetzer geprüft',
        self::REVIEWED_TRANSLATOR_AUTO => 'Übersetzer autogeprüft',
        self::REVIEWED_PM => 'PM geprüft',
        self::REVIEWED_PM_AUTO => 'PM Autogeprüft',
        self::REVIEWED_PM_UNCHANGED => 'PM geprüft, unverändert',
        self::REVIEWED_PM_UNCHANGED_AUTO => 'PM Autogeprüft, unverändert',
    );
    
    /**
     * returns a map with state as index and translated text as value
     * @return array
     */
    public function getLabelMap() {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        //no json_encode because later on passed to php2js, which does json-encoding
        $states = $this->states;
        $states[self::PENDING] = 'wird ermittelt...'; //actually only needed in frontend
        return array_map(function($value) use ($translate) {
            return $translate->_($value);
        }, $states);
    }
    
    /**
     * returns all valid state integers as list
     * @return array
     */
    public function getStates() {
        return array_keys($this->states);
    }
    
    /**
     * returns a mapping between user workflow roles, and segment auto states to be used for this role
     * @return multitype:string
     */
    public function getRoleToStateMap() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        
        //if the user is not assigned to the task directly, but he is allowed to edit the user
        //then the default state would be REVIEWED_PM instead a normal REVIEWED
        if($this->isEditWithoutAssoc()){
            $default = self::REVIEWED_PM;
        }
        else {
            $default = self::REVIEWED;
        }
        
        return array(
          'default' => $default, //missing role fallback for "editAllTasks" users
          $workflow::ROLE_LECTOR => self::REVIEWED,
          $workflow::ROLE_TRANSLATOR => self::REVIEWED_TRANSLATOR
        );
    }
    
    /**
     * returns the state to use for Alikesegments
     * @param integer $originalState The AutoStateId of the master segment which is copied to the alike segments
     * @return integer
     */
    public function calculateAlikeState($originalState) {
        switch ($originalState) {
            case self::REVIEWED:
                return self::REVIEWED_AUTO;
            case self::REVIEWED_UNCHANGED:
                return self::REVIEWED_UNCHANGED_AUTO;
            case self::REVIEWED_PM:
                return self::REVIEWED_PM_AUTO;
            case self::REVIEWED_PM_UNCHANGED:
                return self::REVIEWED_PM_UNCHANGED_AUTO;
            case self::REVIEWED_TRANSLATOR:
                return self::REVIEWED_TRANSLATOR_AUTO;
            default:
                return $originalState;
        }
    }
    
    /**
     * calculates the initial autoStateId of an segment in the import process
     * @param boolean $isEditable
     * @param boolean $isTranslated
     * @return integer
     */
    public function calculateImportState($isEditable, $isTranslated) {
        if(! $isEditable) {
            return self::BLOCKED;
        }
        if($isTranslated) {
            return self::TRANSLATED;
        }
        return self::NOT_TRANSLATED;
    }
    
    /**
     * calculates and returns the autoStateID to use
     * @param editor_Models_Segment $segment
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function calculateSegmentState(editor_Models_Segment $segment, editor_Models_TaskUserAssoc $tua) {
        $isModified = $segment->isDataModified();
        
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        
        if($segment->getAutoStateId() == self::BLOCKED){
            return self::BLOCKED;
        }
        
        if($tua->getRole() == $workflow::ROLE_TRANSLATOR) {
            return self::REVIEWED_TRANSLATOR;
        }
        if($tua->getRole() == $workflow::ROLE_LECTOR) {
            return $isModified ? self::REVIEWED : self::REVIEWED_UNCHANGED;
        }
        
        if($this->isEditWithoutAssoc($tua)){
            return $isModified ? self::REVIEWED_PM : self::REVIEWED_PM_UNCHANGED;
        }
        
        //if no role match, return old value
        return $segment->getAutoStateId(); 
    }
    
    /**
     * sets the untouched state for a given taskGuid
     * 
     * @param string $taskGuid
     * @param editor_Models_Segment $segment
     */
    public function setUntouchedState(string $taskGuid, editor_Models_Segment $segment) {
        $segment->updateAutoState($taskGuid, self::TRANSLATED, self::REVIEWED_UNTOUCHED);
        $segment->updateAutoState($taskGuid, self::NOT_TRANSLATED, self::REVIEWED_UNTOUCHED);
    }
    
    /**
     * sets the untouched state for a given taskGuid back to the initial states
     * 
     * @param string $taskGuid
     * @param editor_Models_Segment $segment
     */
    public function setInitialStates(string $taskGuid, editor_Models_Segment $segment) {
        $segment->updateAutoState($taskGuid, self::REVIEWED_UNTOUCHED, self::NOT_TRANSLATED, true);
        $segment->updateAutoState($taskGuid, self::REVIEWED_UNTOUCHED, self::TRANSLATED);
    }
    
    /**
     * changes the state after add / edit a comment of this task
     * @param editor_Models_Segment $segment
     */
    public function updateAfterCommented(editor_Models_Segment $segment, editor_Models_TaskUserAssoc $tua) {
        if($segment->getAutoStateId() == self::TRANSLATED || $segment->getAutoStateId() == self::NOT_TRANSLATED) {
            if($this->isEditWithoutAssoc($tua)) {
                $stateToSet = self::REVIEWED_PM_UNCHANGED;
            }
            else {
                $stateToSet = self::REVIEWED_UNCHANGED;
            }
            $segment->setAutoStateId($stateToSet);
        }
    }
    
    /**
     * returns true if user has right to edit all Tasks, checks optionally the workflow role of the user
     * @param editor_Models_TaskUserAssoc $tua optional, if not given only acl is considered
     */
    protected function isEditWithoutAssoc(editor_Models_TaskUserAssoc $tua = null) {
        $userSession = new Zend_Session_Namespace('user');
        $role = $tua && $tua->getRole();
        $acl = ZfExtended_Acl::getInstance();
        return empty($role) && $acl->isInAllowedRoles($userSession->data->roles,'editAllTasks');
    }
}