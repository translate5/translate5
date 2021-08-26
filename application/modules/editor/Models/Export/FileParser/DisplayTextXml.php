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
 * Export FileParser for DisplayText XML files
 */
class editor_Models_Export_FileParser_DisplayTextXml extends editor_Models_Export_FileParser {

    /**
     * TODO makes no sense but must be defined
     * @var string
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Csv';
    
    /**
     * replaces <lekTargetSeg... placeholders with segment content and fills the _exportFile data container
     */
    protected function parse() {
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        
        $xmlparser->registerElement('lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser){
            $attributes = $opener['attributes'];
            if(empty($attributes['id']) && $attributes['id'] !== '0') {
                throw new Zend_Exception('Missing id attribute in '.$xmlparser->getChunk($key));
            }
            
            $id = $attributes['id'];
            //alternate field is optional, use target as default
            if(isset($attributes['field'])) {
                $field = $attributes['field'];
            }
            else {
                $field = editor_Models_SegmentField::TYPE_TARGET;
            }
            
            //replace newlines by Linefeed tags
            $segment = str_replace("\n", '<Linefeed/>', $this->getSegmentContent($id, $field));
            $xmlparser->replaceChunk($key, $segment);
        });
        
        $this->_exportFile = $xmlparser->parse($this->_skeletonFile, false);
    }
    
    /**
     * overwrites the parent to remove img tags, which contain currently MQM only (until we provide real MQM export)
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::parseSegment()
     */
    protected function parseSegment($segment) {
        $segment = preg_replace('/<img[^>]*>/','', $segment);
        return parent::parseSegment($segment);
    }
}
