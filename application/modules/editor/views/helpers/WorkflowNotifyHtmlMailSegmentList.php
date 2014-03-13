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
     * This var ist initilized lazy
     * @var editor_Models_Converter_XmlSegmentList
     */
    protected $xmlConverter;
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
        $qmSubFlags = $this->view->task->getQmSubsegmentFlags();
        return $this->modifyQmSubsegments($content);
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
    protected function modifyQmSubsegments($content) {
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
        
        return $this->xmlConverter->convertQmSubsegments($this->view->task, $content, $resultRenderer);
    } 
    
    /**
     * render the HTML Segment Table
     * @return string
     */
    protected function render() {
        $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        /* @var $states editor_Models_SegmentAutoStates */
        $stateMap = $states->getLabelMap();
        
        
        $this->xmlConverter = ZfExtended_Factory::get('editor_Models_Converter_XmlSegmentList');
        
        $t = $this->view->translate;
        if(empty($this->segments)) {
            return '<b>'.$t->_('Es wurden keine Segmente verändert!').'</b>';
        }
        $task = $this->view->task;
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
                $fieldsToShow[$sfm->getEditIndex($field->name)] = $t->_($field->label.' - bearbeitet');
            }
        }
        
        /* @var $task editor_Models_Task */
        $result = array('<br /><br /><table cellpadding="4">');
        $th = '<th align="left" valign="top">';
        $result[] = '<tr>';
        $result[] = $th.$t->_('Nr.').'</th>';
        foreach($fieldsToShow as $field) {
            $result[] = $th.$field.'</th>';
        }
        $result[] = $th.$t->_('Status').'</th>';
        $result[] = $th.$t->_('QM').'</th>';
        $result[] = $th.$t->_('AutoStatus').'</th>';
        $result[] = $th.$t->_('Matchrate').'</th>';
        $result[] = $th.$t->_('Kommentare').'</th>';
        $result[] = '</tr>';
        
        $translateQm = function($qm) use ($t) {
            return $t->_($qm);
        };
        
        foreach($this->segments as $segment) {
            $state = isset($stateMap[$segment['autoStateId']]) ? $stateMap[$segment['autoStateId']] : '- not found -'; //else tree should not be so untranslated
            $result[] = "\n".'<tr>';
            $result[] = '<td valign="top">'.$segment['segmentNrInTask'].'</td>';
            foreach($fieldsToShow as $fieldName => $field) {
                $result[] = '<td valign="top">'.$this->prepareSegment($segment[$fieldName]).'</td>';
            }
            $result[] = '<td valign="top" nowrap="nowrap">'.$t->_($this->xmlConverter->convertStateId($segment['stateId'])).'</td>';
            $qms = array_map($translateQm, $this->xmlConverter->convertQmIds($segment['qmId']));
            $result[] = '<td valign="top" nowrap="nowrap">'.join(',<br />', $qms).'</td>';
            $result[] = '<td valign="top">'.$state.'</td>';
            $result[] = '<td valign="top">'.$segment['matchRate'].'%</td>';
            $result[] = '<td valign="top">'.$this->prepareComments($segment['comments']).'</td>';
            $result[] = '</tr>';
        }
        $result[] = '</table>';
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
            $this->segmentHash = md5(print_r($segments, 1));
        }
        else {
            $this->segmentHash = $segmentHash;
        }
        $this->segments = $segments;
        return $this;
    }
}