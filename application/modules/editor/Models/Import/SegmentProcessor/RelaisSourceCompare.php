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
 * Compares the segment-source of a relais-source with it's segment-source
 * Also generalizes the errors that may occur
 * This is used for the Relais-Processor but also the Visual Reviews aligned layout
 */
class editor_Models_Import_SegmentProcessor_RelaisSourceCompare {

    /* Definitions of the different relais compare mode flags */
    /**
     * @var integer
     */
    const MODE_IGNORE_TAGS = 1;
    /**
     * @var integer
     */
    const MODE_NORMALIZE_ENTITIES = 2;
    
    /**
     * @var Integer
     */
    private $configuredCompareMode = 0;
    /**
     * @var editor_Models_Segment_InternalTag
     */
    private $internalTag;


    public function __construct() {
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        // parse  compare-mode configured by runtime-options
        $config = Zend_Registry::get('config');
        $modes = $config->runtimeOptions->import->relaisCompareMode;
        foreach($modes as $mode) {
            $this->configuredCompareMode += constant('self::MODE_'.$mode);
        }
    }
    /**
     * returns true if content is equal
     * equal means here, that also the tags must be equal in content and position
     * @param string $source
     * @param string $relais
     * @return boolean
     */
    public function isEqual(string $source, string $relais): bool {
        return $this->normalize($source) === $this->normalize($relais);
    }
    /**
     * Checks if content is empty using the crrent normalization
     * @param string $content
     * @return bool
     */
    public function isEmpty(string $content): bool {
        return (strlen(trim($this->normalize($content))) == 0);
    }
    /**
     * The given segment content is normalized for source / relais source comparsion
     * Currently all tags are removed (means ignored). To keep word boundaries the tags
     * are replaced with whitespace, multiple whitespaces are replaced to a single one
     * HTML Entities are decoded to enable comparsion of " and &quot;
     *
     * @param string $content
     * @return string
     */
    public function normalize(string $content) {
        if($this->configuredCompareMode & self::MODE_IGNORE_TAGS) {
            $content = $this->internalTag->replace($content, ' ');
            // internal tags may represent whitespace of the original text which are now converted to blanks,
            // to enable a proper comparision we have to normalize the whitespace
            //trim removes leading / trailing whitespaces added by tag removing
            $content = trim(editor_Utils::normalizeWhitespace($content, ' '));
        }
        if($this->configuredCompareMode & self::MODE_NORMALIZE_ENTITIES){
            return html_entity_decode($content);
        }
        return $content;
    }
}