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
 * Formats a Segment List as a HTML table to be send as an E-Mail. 
 */
class View_Helper_WorkflowNotifyHtmlMailSegmentList extends Zend_View_Helper_Abstract
{
    protected static $segmentCache = array();
    
    /**
     * segment list
     * @var array
     */
    protected $segments;
    
    /**
     * This vars are initialized lazy
     * @var editor_Models_Segment_Utility
     */
    protected $segmentUtility;
    
    /**
     * This vars are initialized lazy
     * @var editor_Models_Segment_Mqm
     */
    protected $mqmConverter;
    
    /**
     * replace the comment HTML Tags with <br>
     * @param string $comments
     * @return string
     */
    protected function prepareComments($comments) {
        $search = array('<span class="author">', '<span class="modified">', '</div>');
        $replace = array("~#br#~", ' (', ") ~#br#~~#br#~");
        $comments = str_replace($search, $replace, $comments);
        $comments = str_replace('~#br#~', '<br />', strip_tags($comments));
        return $comments;
    }
    
    /**
     * replace Segment HTML with E-Mail usable HTML
     * @param string $content
     * @return string
     */
    protected function prepareSegment($content) {
        //remove full tags
        $content = preg_replace('#<span[^>]+class="full"[^>]*>[^<]*</span>#i', '', $content);
        //replace short tag div span construct to a simple span 
        $content = preg_replace('#<div[^>]+>[\s]*<span([^>]+)class="short"([^>]*)>([^<]*)</span>[\s]*</div>#mi', '<span $1 $2 style="background-color:#39ffa3;">$3</span>', $content);
        //replace term divs by breaking apart to replace the class
        $parts = preg_split('#(<div[^>]+>)#i', $content, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $idx => $part) {
            if(! ($idx % 2)) {
                continue;
            }
            $parts[$idx] = $this->modifyTermTag($part);
        }
        $content = str_ireplace('</div>', '</span>', join('', $parts));
        return $this->modifyMqmTags($content);
    }
    
    /**
     * replaces the current term tag with a span tag, containing styles instead css classes
     * In this method the current used term styles are adapted (see main.css)
     * 
     * @param string $termTag
     * @return string
     */
    protected function modifyTermTag($termTag) {
        $cls = explode(' ', preg_replace('#<div[^>]+class="([^"]*)"[^>]*>#i', '$1', $termTag));
        $title = preg_replace('#<div[^>]+title="([^"]*)"[^>]*>#i', '$1', $termTag);
        $result = '<span title="%1$s" style="%2$s">';
        
        //adapted css logic:
        if(in_array('notRecommended', $cls) || in_array('supersededTerm', $cls) || in_array('deprecatedTerm', $cls)) {
            return sprintf($result, $title, 'border-bottom:none;background-color:#fa51ff;');
        }
        if(in_array('transNotFound', $cls)) {
            return sprintf($result, $title, 'border-bottom-color:#ff0000;');
        }
        if(in_array('transNotDefined', $cls)) {
            return sprintf($result, $title, 'border-bottom-color:#8F4C36;');
        }
        if(in_array('term', $cls)) {
            return sprintf($result, $title, 'background:transparent;border-bottom:1px solid #0000ff;');
        }
        return '<span>';
    }
    
    /**
     * modifies the QM Subsegment Tags as needed
     * @param string $content
     * @return string
     */
    protected function modifyMqmTags($content) {
        $translate = $this->view->translate;
        $resultRenderer = function($tag, $cls, $issueId, $issueName, $sev, $sevName, $comment) use ($translate){
            $title = empty($sevName) ? '' : htmlspecialchars($translate->_($sevName)).': ';
            $title .= htmlspecialchars(empty($issueName) ? '' : $translate->_($issueName));
            $title .= empty($comment) ? '' : ' / '.$comment;
            
            $span = '<span style="background-color:#ff8215;" title="%1$s"> %2$s </span>';
            if(in_array('open', $cls)) {
                return sprintf($span, $title, '['.$issueId);
            }
            return sprintf($span, $title, $issueId.']');
        };
        
        return $this->mqmConverter->replace($this->view->task, $content, $resultRenderer);
    } 
    
    /**
     * render the HTML Segment Table
     * @return string
     */
    protected function render() {
        //the segments list should not be send to reviewers when the previous workflow step was translations
        if(isset($this->view->triggeringRole) && $this->view->triggeringRole == editor_Workflow_Abstract::ROLE_TRANSLATOR){
            return '';
        }
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $stateMap = $states->getLabelMap();
        
        $this->mqmConverter = ZfExtended_Factory::get('editor_Models_Segment_Mqm');
        $this->segmentUtility = ZfExtended_Factory::get('editor_Models_Segment_Utility');
        
        $t = $this->view->translate;
        if(empty($this->segments)) {
            return '<b>'.$t->_('Es wurden keine Segmente verändert!').'</b>';
        }
        
        $task = $this->view->task;
        /* @var $task editor_Models_Task */
        
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        
        $fields = $sfm->getFieldList();
        $fieldsToShow = array();
        foreach($fields as $field) {
            if($field->type == editor_Models_SegmentField::TYPE_RELAIS) {
                continue;
            }
            //show the original source
            if($field->type == editor_Models_SegmentField::TYPE_SOURCE) {
                $fieldsToShow[$field->name] = $t->_($field->label);
            }
            //if field is editable (source or target), show the edited data
            if($field->editable) {
                $fieldsToShow[$sfm->getEditIndex($field->name)] = sprintf($t->_('%s - bearbeitet'), $t->_($field->label));
            }
        }
        
        $result = [];
        $result[] = '<br/>';
        $header = $t->_('Im folgenden die getätigten Änderungen der vorhergehenden Rolle <b>{previousRole}</b>:<br />');
        $header = str_replace('{previousRole}', $t->_($this->view->triggeringRole), $header);
        
        $result[] = $header;
        
        $result[] = '<br /><br /><table cellpadding="4">';
        $th = '<th align="left" valign="top">';
        $result[] = '<tr>';
        $result[] = $th.$t->_('Nr.').'</th>';
        foreach($fieldsToShow as $field) {
            $result[] = $th.$field.'</th>';
        }
        $result[] = $th.$t->_('Status').'</th>';
        $result[] = $th.$t->_('QM').'</th>';
        $result[] = $th.$t->_('Bearbeitungsstatus').'</th>';
        $result[] = $th.$t->_('Matchrate').'</th>';
        $result[] = $th.$t->_('Kommentare').'</th>';
        $result[] = '</tr>';
        
        $translateQm = function($qm) use ($t) {
            return $t->_($qm);
        };
        
        foreach($this->segments as $segment) {
            $state = $stateMap[$segment['autoStateId']] ?? '- not found -'; //else tree should not be so untranslated
            $result[] = "\n".'<tr>';
            $result[] = '<td valign="top">'.$segment['segmentNrInTask'].'</td>';
            foreach($fieldsToShow as $fieldName => $field) {
                $result[] = '<td valign="top">'.$this->prepareSegment($segment[$fieldName]).'</td>';
            }
            $result[] = '<td valign="top" nowrap="nowrap">'.$t->_($this->segmentUtility->convertStateId($segment['stateId'])).'</td>';
            $qms = array_map($translateQm, $this->segmentUtility->convertQmIds($segment['qmId']));
            $result[] = '<td valign="top" nowrap="nowrap">'.join(',<br />', $qms).'</td>';
            $result[] = '<td valign="top">'.$t->_($state).'</td>';
            $result[] = '<td valign="top">'.$segment['matchRate'].'%</td>';
            $result[] = '<td valign="top">'.$this->prepareComments($segment['comments']).'</td>';
            $result[] = '</tr>';
        }
        $result[] = '</table>';
        $result[] = '<br/>';
        return join('', $result);
    }
    
    /**
     * @return string
     */
    public function __toString(){
        if(empty(self::$segmentCache[$this->segmentHash])) {
            self::$segmentCache[$this->segmentHash] = $this->render();
        }
        return self::$segmentCache[$this->segmentHash];
    }

    /**
     * Helper Initiator
     * @param array $segments
     * @param string $segmentHash optional hash to identify the segments to cash them internally
     */
    public function workflowNotifyHtmlMailSegmentList(array $segments, $segmentHash = null) {
        if(empty($segmentHash)) {
            $this->segmentHash = md5(print_r($segments, 1).$this->view->translate->getTargetLang());
        }
        else {
            $this->segmentHash = $segmentHash;
        }
        $this->segments = $segments;
        return $this;
    }
}