<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    const ATTRIBUTE_USERTRACKINGID          = 'data-usertrackingid';
    const ATTRIBUTE_USERCSSNR               = 'data-usercssnr';
    const ATTRIBUTE_WORKFLOWSTEP            = 'data-workflowstep';
    const ATTRIBUTE_TIMESTAMP               = 'data-timestamp';
    const ATTRIBUTE_HISTORYLIST             = 'data-historylist';
    const ATTRIBUTE_HISTORY_SUFFIX          = '_history_';
    const ATTRIBUTE_ACTION                  = 'data-action';
    const ATTRIBUTE_USERCSSNR_VALUE_PREFIX  = 'usernr';
    
    /**
     * the id of the user in the taskUserTracking-table
     */
    public $userTrackingId;
    
    /**
     * number for usercss-color (= taskOpenerNumber from taskUserTracking)
     */
    public $userColorNr;
    
    /***
     * Trackchanges workflow step attribute
     * @var string
     */
    public $attributeWorkflowstep;

    /**
     * Used in the termtagging process to bind related data
     * @var string
     */
    public $textWithTrackChanges;
    
    
    public function __construct(){
        $this->replacerRegex = self::REGEX_DEL;
        $this->placeholderTemplate = '<'.self::PLACEHOLDER_TAG_DEL.' id="%s" />';
    }
    
    /***
     * Create trackchanges html node as string 
     *
     * @param string $nodeName
     * @param string $nodeText
     * @return string|string
     */
    public function createTrackChangesNode($nodeName,$nodeText){
        
        $node = [];
        $node[] = '<'.$nodeName;
        $node[] = 'class="'.$this->getTrackChangesCss($nodeName).'"';
        
        // id to identify the user who did the editing (also used for verifying checks)
        $node[] = self::ATTRIBUTE_USERTRACKINGID.'="'.$this->userTrackingId.'"';
        
        // css-selector with specific number for this user
        $node[] = self::ATTRIBUTE_USERCSSNR.'="'.self::ATTRIBUTE_USERCSSNR_VALUE_PREFIX.$this->userColorNr.'"';
        
        //workflow-step:
        $node[] = self::ATTRIBUTE_WORKFLOWSTEP.'="'.$this->attributeWorkflowstep.'"';
        
        // timestamp af the change:
        $node[] = self::ATTRIBUTE_TIMESTAMP.'="'.date("c").'"';
        
        $node[] = '>'.$nodeText.'</'.$nodeName.'>';
        
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
     * This function returns a list of used track change tags inside text in a canonical comparable format, since comparing bare tags is due different structure not possible
     * @param string $segment
     * @return array
     */
    public function getUsedTagInfo(string $segment): array {
        $matches= null;
        if(!preg_match_all('/<(ins|del) ([^>]+)>/', $segment, $matches)) {
            return [];
        }
        $result = [];
        foreach($matches[1] as $index => $tag) {
            $dataAttributes = [];
            //find data-attributes
            if(preg_match_all('/data-([^\s]+)="([^"]*)"/', $matches[2][$index], $dataAttributes)){
                //ensure that all attribute keys are lowercase, original notation can be found in the orignal chunk
                $dataAttributes[1] = array_map('strtolower', $dataAttributes[1]);
                $tag .= '#'.join('#', array_combine($dataAttributes[1], $dataAttributes[2]));
            }
            $result[] = $tag;
        }
        return $result;
    }
}