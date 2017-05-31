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
 * trait containing tag mapping logic of import process
 * 
 * For refactoring the import process to a better understandable structure some code is moved into traits to keep refactoring steps small! 
 */
trait editor_Models_Import_FileParser_TagTrait {
    
    /**
     * @var editor_ImageTag_Left
     */
    protected $_leftTag = NULL;

    /**
     * @var editor_ImageTag_Right
     */
    protected $_rightTag = NULL;

    /**
     * @var editor_ImageTag_Single
     */
    protected $_singleTag = NULL;
    
    /**
     * defines the GUI representation of internal used tags for masking special characters  
     * @var array
     */
    protected $_tagMapping = array(
        'hardReturn' => array('text' => '&lt;hardReturn/&gt;', 'imgText' => '<hardReturn/>'),
        'softReturn' => array('text' => '&lt;softReturn/&gt;', 'imgText' => '<softReturn/>'),
        'macReturn' => array('text' => '&lt;macReturn/&gt;', 'imgText' => '<macReturn/>'),
        'space' => array('text' => '&lt;space/&gt;', 'imgText' => '<space/>'));

    /**
     * to be called in the constructors
     */
    protected function initImageTags(){
        $this->_leftTag = ZfExtended_Factory::get('editor_ImageTag_Left');
        $this->_rightTag = ZfExtended_Factory::get('editor_ImageTag_Right');
        $this->_singleTag = ZfExtended_Factory::get('editor_ImageTag_Single');
    }

    /**
     * callback for replace method in parseSegment
     * @param array $match
     * @return string
     */
    protected function whitespaceTagReplacer(array $match) {
        //$replacer = function($match) use ($segment, $shortTagIdent, $map) {
        $tag = $match[0];
        $tagName = preg_replace('"<([^/ ]*).*>"', '\\1', $tag);
        if(!isset($this->_tagMapping[$tagName])) {
            trigger_error('The used tag ' . $tagName .' is undefined! Segment: '.$this->_segment, E_USER_ERROR);
        }
        $fileNameHash = md5($this->_tagMapping[$tagName]['imgText']);
        
        //generate the html tag for the editor
        $p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $fileNameHash);
        $tag = $this->_singleTag->getHtmlTag($p);
        $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagName]['imgText'], $fileNameHash);
        $this->_tagCount++;
        return $tag;
    }
    
    /**
     * returns the parameters for creating the HtmlTags used in the GUI
     * @param string $tag
     * @param string $shortTag
     * @param string $tagId
     * @param string $fileNameHash
     * @param string $text
     */
    protected function getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text = false) {
        if($text === false) {
            $text = $this->_tagMapping[$tagId]['text'];
        }
        return array(
            'class' => $this->parseSegmentGetStorageClass($tag),
            'text' => $text,
            'shortTag' => $shortTag,
            'id' => $tagId, //mostly original tag id
            'filenameHash' => $fileNameHash,
        );
    }
    
    /**
     * Hilfsfunktion für parseSegment: Verpackung verschiedener Strings zur Zwischenspeicherung als HTML-Klassenname im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @param boolean $locked gibt an, ob der übergebene Tag die Referenzierung auf einen gesperrten inline-Text im sdlxliff ist
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetStorageClass($tag) {
        $tagContent = preg_replace('"^<(.*)>$"', '\\1', $tag);
        if($tagContent == $tag){
            trigger_error('The Tag ' . $tag .
                    ' has not the structure of a tag.', E_USER_ERROR);
        }
        return implode('', unpack('H*', $tagContent));
    }
}
