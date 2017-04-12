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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Segment QmSubsegments Helper Class
 */
class editor_Models_Segment_QmSubsegments {
    
    protected static $issueCache = array();
    protected static $severityCache = array();
    
    /**
     * break the qm img tags apart and the apply the $replacer to manipulate the tag
     * $replacer is a Closure and returns the converted string. 
     * It accepts the following parameters:
     *     string $tag = original img tag, 
     *     array $cls css classes, 
     *     integer $issueId the qm issue id, 
     *     string $issueName the untranslated qm issue name, 
     *     string $sev the untranslated sev textual id, 
     *     string $sevName the untranslated sev string, 
     *     string $comment the user comment
     * 
     * @param editor_Models_Task $task
     * @param string $segment
     * @param Closure $replacer does the final rendering of the qm tag, Parameters see above
     */
    public function replace(editor_Models_Task $task, string $segment, Closure $replacer) {
        $qmSubFlags = $task->getQmSubsegmentFlags();
        if(empty($qmSubFlags)){
            return $segment;
        }
        $this->initCaches($task);
        $parts = preg_split('#(<img[^>]+>)#i', $segment, null, PREG_SPLIT_DELIM_CAPTURE);
        $tg = $task->getTaskGuid();
        $severities = array_keys(get_object_vars(self::$severityCache[$tg]));
        foreach($parts as $idx => $part) {
            if(! ($idx % 2)) {
                continue;
            }
            //<img  class="critical qmflag ownttip open qmflag-1" data-seq="412" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-left.png" />
            preg_match('#<img[^>]+(class="([^"]*(qmflag-([0-9]+)[^"]*))"[^>]+data-comment="([^"]*)")|(data-comment="([^"]*)"[^>]+class="([^"]*(qmflag-([0-9]+)[^"]*))")[^>]*>#i', $part, $matches);
            $cnt = count($matches);
            if($cnt < 6) {
                $parts[$idx] = $part;
                continue;
            }
            if(count($matches) > 10) {
                $cls = explode(' ', $matches[8]);
                $issueId = $matches[10];
                $comment = $matches[7];
            }
            else {
                $cls = explode(' ', $matches[2]);
                $issueId = $matches[4];
                $comment = $matches[5];
            }
            
            $sev = array_intersect($severities, $cls);
            $sev = reset($sev);
            $sev = empty($sev) ? 'sevnotfound' : $sev;
            $sevName = (isset(self::$severityCache[$tg]->$sev) ? self::$severityCache[$tg]->$sev : '');
            $issueName = (isset(self::$issueCache[$tg][$issueId]) ? self::$issueCache[$tg][$issueId] : '');
            
            $parts[$idx] = $replacer($part, $cls, $issueId, $issueName, $sev, $sevName, $comment);
        }
        return join('', $parts);
    }
    
    /**
     * replaces internal tags with either the callback or the given scalar
     * @see preg_replace
     * @see preg_replace_callback
     * @param string $segment
     * @param Closure|string $replacer
     * @param int $limit optional
     * @param int $count optional, returns the replace count
     * @return mixed 
     */
    public function replace(string $segment, $replacer, $limit = -1, &$count = null) {
        if(is_callable($replacer)) {
            return preg_replace_callback(self::REGEX_INTERNAL_TAGS, $replacer, $segment, $limit, $count);
        }
        return preg_replace(self::REGEX_INTERNAL_TAGS, $replacer, $segment, $limit, $count);
    }
    
    /**
     * caches task issues and severities
     * @param editor_Models_Task $task
     */
    protected function initCaches(editor_Models_Task $task) {
        $tg = $task->getTaskGuid();
        if(empty(self::$issueCache[$tg])){
            self::$issueCache[$tg] = $task->getQmSubsegmentIssuesFlat();
        }
        if(empty(self::$severityCache[$tg])){
            self::$severityCache[$tg] = $task->getQmSubsegmentSeverities();
        }
    }
}