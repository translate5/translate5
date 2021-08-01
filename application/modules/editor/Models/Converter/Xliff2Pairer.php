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

/**
 */
class editor_Models_Converter_Xliff2Pairer extends editor_Models_Converter_XmlPairer {
    /**
     * @var array
     */
    protected $validOpenTags = ['sc'];
    
    /**
     * @var array
     */
    protected $validCloseTags = ['ec'];
    
    protected function getOpener(editor_Models_Converter_XmlPairerNode $node) {
        return '<pc id="'.$node->id.'">';
    }
    
    protected function getCloser(editor_Models_Converter_XmlPairerNode $node) {
        return '</pc>';
    }
    
    /**
     * creates a XmlPairerNode by the given nod
     * @param int $idx
     * @param string $node
     * @return boolean|editor_Models_Converter_XmlPairerNode
     */
    protected function createNode($idx, $node) {
        $matches = null;
        //if no tag or a tag without rid information, ignore
        if(!preg_match('#<([^\s>]+)[^>]+id="([^"]+)"([\s]+startRef="([^"]+)")?[^>]*>#', $node, $matches)) {
            return false;
        }
        
        $tag = $matches[1];
        $id = $matches[2];
        $rid = empty($matches[4]) ? false : $matches[4];
        
        //ignore non paired tags
        if(!in_array($tag, $this->validOpenTags) && !in_array($tag, $this->validCloseTags)) {
            return false;
        }
        
        //init node data, $open = ($tag == 'bx' || $tag == 'bpt')
        $opener = (in_array($tag, $this->validOpenTags));
        //for opener tags use id as rid, for closer tags the rid found in startRef
        return new editor_Models_Converter_XmlPairerNode($idx, $opener, $opener ? $id : $rid, $id);
    }
}
