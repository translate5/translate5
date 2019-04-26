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

/***
 * Track changes tag replacer
 * 
 * @author aleksandar
 * 
 * @method string protect() protect(string $segment) protects the DEL tags of one segment
 * @method string unprotect() unprotect(string $segment) unprotects / restores the DEL tags
 * @method string replace() replace(string $segment, Closure|string $replacer, int $limit = -1, int &$count = null) replaces DEL tags with either the callback or the given scalar
 */
class editor_Models_Segment_TrackChangeTag extends editor_Models_Segment_TagAbstract{
    
    /***
     * del-Tag:  including their content!
     * @var string
     */
    const REGEX_DEL     = '/<del[^>]*>.*?<\/del>/i';
    
    /***
     * ins-Tag:  only the tags without their content
     * @var string
     */
    const REGEX_INS     = '/<\/?ins[^>]*>/i';
    
    /**
     * FIXME currently used only at one place, refactor so that this class provides a function to achieve the same stuff as currently done in ReplaceMatchesSegment.php
     * del protected tag regex 
     * @var string
     */
    const REGEX_PROTECTED_DEL='/<segment:del[^>]+((id="([^"]*)"[^>]))[^>]*>/';
    
    /***
     * trackchange placeholder template 
     * @var string
     */
    const PLACEHOLDER_TAG_DEL='segment:del';
    
    /***
     * delete node name 
     */
    const NODE_NAME_DEL='del';
    
    /***
     * insert node name 
     * @var string
     */
    const NODE_NAME_INS='ins';
    
    /***
     * insert tag css class 
     * @var string
     */
    const CSS_CLASSNAME_INS='trackchanges ownttip';
    
    /***
     * delete tag css class 
     * @var string
     */
    const CSS_CLASSNAME_DEL='trackchanges ownttip deleted';
    
    /***
     * Attributes for the trackchange-Node 
     * @var string
     */
    const ATTRIBUTE_USERGUID='data-userguid';
    const ATTRIBUTE_USERNAME='data-username';
    const ATTRIBUTE_USERCSSNR='data-usercssnr';
    const ATTRIBUTE_WORKFLOWSTEP='data-workflowstep';
    const ATTRIBUTE_TIMESTAMP='data-timestamp';
    const ATTRIBUTE_HISTORYLIST='data-historylist';
    const ATTRIBUTE_HISTORY_SUFFIX='_history_';
    const ATTRIBUTE_ACTION='data-action';
    const ATTRIBUTE_USERCSSNR_VALUE_PREFIX='usernr';
    
    /**
     * Array of JSON with the userColorMap from DB's task_meta-Info
     */
    public $userColorNr;
    
    /***
     * Trackchanges workflow step attribute
     *
     * @var unknown
     */
    public $attributeWorkflowstep;
    
    
    public function __construct(){
        $this->replacerRegex=self::REGEX_DEL;
        $this->placeholderTemplate='<'.self::PLACEHOLDER_TAG_DEL.' id="%s" />';
    }
    
    /***
     * Create trackchanges html node as string 
     *
     * @param string $nodeName
     * @param string $nodeText
     * @return string|string
     */
    public function createTrackChangesNode($nodeName,$nodeText){
        
        $sessionUser = new Zend_Session_Namespace('user');
        
        $node=[];
        $node[]='<'.$nodeName;
        $node[]='class="'.$this->getTrackChangesCss($nodeName).'"';
        
        // id to identify the user who did the editing (also used for verifying checks)
        $node[]=self::ATTRIBUTE_USERGUID.'="'.$sessionUser->data->userGuid.'"';
        
        // name of the user who did the editing
        $node[]=self::ATTRIBUTE_USERNAME.'="'.$sessionUser->data->userName.'"';
        
        // css-selector with specific number for this user
        $node[]=self::ATTRIBUTE_USERCSSNR.'="'.self::ATTRIBUTE_USERCSSNR_VALUE_PREFIX.$this->userColorNr.'"';
        
        //workflow-step:
        $node[]=self::ATTRIBUTE_WORKFLOWSTEP.'="'.$this->attributeWorkflowstep.'"';
        
        // timestamp af the change:
        $node[]=self::ATTRIBUTE_TIMESTAMP.'="'.date("c").'"';
        
        $node[]='>'.$nodeText.'</'.$nodeName.'>';
        
        return implode(' ', $node);
        
    }
    
    /***
     * Get trachckanges css class based on the node type
     *
     * @param string $nodeName
     * @return string
     */
    public function getTrackChangesCss($nodeName){
        switch(strtolower($nodeName)) {
            case self::NODE_NAME_DEL:
                return self::CSS_CLASSNAME_DEL;
            case self::NODE_NAME_INS:
                return self::CSS_CLASSNAME_INS;
        }
    }
    
    /**
     * removes TrackChanges-Tags:
     * - INS => markup-Tag ONLY is removed (doing this first is important in order to catch the spaces in the next step:)
     * - DEL => avoid multiple space after removing a deleted word with one or more space at both sides
     * - DEL => markup-Tag AND content inbetween is removed
     */
    public function removeTrackChanges(string $segment) {
        $segment = $this->protect($segment);
        $segment= preg_replace(self::REGEX_INS, '', $segment);
        $segment= preg_replace('/ +<'.self::PLACEHOLDER_TAG_DEL.'[^>]+> +/', ' ', $segment);
        $segment= preg_replace('/<'.self::PLACEHOLDER_TAG_DEL.'[^>]+>/', '', $segment);
        return $segment;
    }
    
    /**
     * anonymizes user-data in TrackChange-Tags in the given string 
     * (= replace data-userguid und data-username with anonymized version)
     * @param string $text
     * @param string $taskGuid (needed to access TaskUserTracking if matches are found)
     * @return string
     */
    public function renderAnonymizedTrackChangeData (string $text, $taskGuid) {
        return $text;
        // TODOs before using this:
        // - update TrackChange-Plugin to not use username and guid eg when checking for same user etc
        
        if (!$this->hasUserdataToAnonymize($text, $taskGuid)) { 
            return $text;
        }
        
        $output = [];
        $trackingData = array();
        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        /* @var $workflowAnonymize editor_Workflow_Anonymize */
        $anonymizeUserguid = function($matchUserguid) use ($output, $taskGuid, &$trackingData, $userModel, $workflowAnonymize) {
            parse_str($matchUserguid[0],$output);
            $userGuid = trim($output['data-userguid'],'"');
            try {
                $userModel->loadByGuid($userGuid);
            } catch (Exception $e) {
                return $matchUserguid[0];
            }
            $userName = $userModel->getUserName();
            $userNameAnon = $workflowAnonymize->renderAnonymizedUserName($userName, $userGuid, $taskGuid);
            if(!array_key_exists($userName, $trackingData)){
                $trackingData[$userName] = $userNameAnon;
            }
            return 'data-userguid="'.$userNameAnon.'"'; // keep this info in order to later get the userGuid again
        };
        $anonymizeUsername = function($matchUsername) use ($output, &$trackingData) {
            parse_str($matchUsername[0],$output);
            $userName = trim($output['data-username'],'"');
            if(array_key_exists($userName, $trackingData)) {
                return 'data-username="'.$trackingData[$userName].'"';
            } else {
                return $matchUsername[0];
            }
        };
        
        $text = preg_replace_callback(
            '/data-userguid=".*?"/',
            $anonymizeUserguid,
            $text);
        $text = preg_replace_callback(
            '/data-username=".*?"/',
            $anonymizeUsername,
            $text);
        return $text;
    }
    
    /**
     * resets anonymized user-data in TrackChange-Tags in the given string
     * (= replace data-userguid und data-username with real userguid and username)
     * @param string $text
     * @param string $taskGuid (needed to access TaskUserTracking if matches are found)
     * @return string
     */
    public function renderUnanonymizedTrackChangeData (string $text, $taskGuid) {
        return $text;
        // TODOs before using this:
        // - update TrackChange-Plugin to not use username and guid eg when checking for same user etc
        
        if (!$this->hasUserdataToAnonymize($text, $taskGuid)) {
            return $text;
        }
        
        $output = [];
        $trackingData = array();
        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $taskUserTracking editor_Models_TaskUserTracking */
        $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        /* @var $workflowAnonymize editor_Workflow_Anonymize */
        $unanonymizeUsername = function($matchUsername) use ($output, $taskGuid, $taskUserTracking, &$trackingData, $userModel, $workflowAnonymize) {
            parse_str($matchUsername[0],$output);
            $userNameAnon = trim($output['data-username'],'"');
            $userName = $workflowAnonymize->renderUnanonymizedUserName($userNameAnon, $userNameAnon, $taskGuid);
            try {
                $taskUserTracking->loadEntryByUserName($taskGuid, $userName);
            } catch (Exception $e) {
                return $matchUsername[0];
            }
            $userGuid = $taskUserTracking->getUserGuid();
            if(!array_key_exists($userNameAnon, $trackingData)){
                $trackingData[$userNameAnon] = $userGuid;
            }
            return 'data-username="'.$userName.'"';
        };
        $unanonymizeUserguid = function($matchUserguid) use ($output, &$trackingData) {
            parse_str($matchUserguid[0],$output);
            $userNameAnon = trim($output['data-userguid'],'"'); // the anonymized version of the userguid contains the anonymized username
            if(array_key_exists($userNameAnon, $trackingData)) {
                return 'data-userguid="'.$trackingData[$userNameAnon].'"';
            } else {
                return $matchUserguid[0];
            }
        };
        
        $text = preg_replace_callback(
            '/data-username=".*?"/',
            $unanonymizeUsername,
            $text);
        $text = preg_replace_callback(
            '/data-userguid=".*?"/',
            $unanonymizeUserguid,
            $text);
        return $text;
    }
    
    /**
     * Does the given text contain any user-related data for anonymizing?
     * @param string $text
     * @return boolean
     */
    protected function hasUserdataToAnonymize($text, $taskGuid) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        if (!$task->anonymizeUsers()) {
            // If the task's users are not to be anonymized, there is nothing to anonymize anyway
            return false;
        }
        
        $hasUserguid = preg_match('/data-userguid=".*?"/', $text);
        if ($hasUserguid === 1) {
            return true;
        }
        $hasUsername = preg_match('/data-username=".*?"/', $text);
        if ($hasUsername === 1) {
            return true;
        }
        return false;
    }

}