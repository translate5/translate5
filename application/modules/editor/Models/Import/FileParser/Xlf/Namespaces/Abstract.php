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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On abstract class
 */
abstract class editor_Models_Import_FileParser_Xlf_Namespaces_Abstract {
    /**
     * Gives the Namespace class the ability to add custom handlers to the xmlparser
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser){
        //method stub
    }
    
    /**
     * Provides a invocation for parsing custom trans-unit attributes
     * @param array $attributes
     * @param editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes
     */
    public function transunitAttributes(array $attributes, editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes){
        //method stub
    }
    
    /**
     * Returns the Translate5 internal tag pair to the given XLF tag pair (<g>, <ept> etc..) from the internal tagmap stored in translate5 XLF
     * @param string $xlfBeginTag
     * @param string $xlfEndTag
     * @return array the internal tag pair to the given xlf tag pair
     */
    public function getPairedTag($xlfBeginTag, $xlfEndTag){
        //method stub
    }
    
    /**
     * Returns the Translate5 internal single tag to the given XLF single tag (<x>, <it> etc..) from the internal tagmap stored in translate5 XLF
     * @param string $xlfTag
     * @return array the internal tag to the given xlf single tag
     */
    public function getSingleTag($xlfTag){
        //method stub
    }
    
    /**
     * returns if the used XLIFF derivate must or must not use the plain tag content as internal tag text, or null if should depend on the tag
     * @return boolean|NULL
     */
    abstract public function useTagContentOnly();
    
    /**
     * Returns found comments, to be implemented in the subclasses!
     * @return array
     */
    public function getComments() {
        //method stub
        return [];
    }
}
