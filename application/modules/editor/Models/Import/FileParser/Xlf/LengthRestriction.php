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

/**
 * Handles LengthRestriction (reading needed attributes) on XLIFF import 
 */
class editor_Models_Import_FileParser_Xlf_LengthRestriction {
    /**
     * @var editor_Models_PixelMapping
     */
    protected $pixelMapping;
    
    /**
     * Container for the lengthRestriction default values
     * @var array
     */
    protected $lengthRestrictionDefaults = ['size-unit' => null, 'minWidth' => null, 'maxWidth' => null, 'maxNumberOfLines' => null, 'font' => null, 'fontSize' => null];
    
    /**
     * Some of the keys in $lengthRestrictionDefaults are not standard xliff-Attributes, but named differently in the trans-units and our SegmentAttributes.
     * @var array
     */
    protected static $nonStandardXliffAttributes = ['size-unit' =>        ['inUnit' => 'size-unit',                   'inSegmentAttr' => 'sizeUnit'],
                                                    'maxNumberOfLines' => ['inUnit' => 'translate5:maxNumberOfLines', 'inSegmentAttr' => 'maxNumberOfLines'] ];
    
    
    public function __construct() {
        $this->pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');
        $this->initLengthRestrictionAttributes();
    }
    
    /**
     * overwrite default values for length-Restriction-Attributes from task template (sizeUnit, minWidth, maxWidth, maxNumberOfLines, font, fontSize):
     */
    protected function initLengthRestrictionAttributes () {
        if(!Zend_Registry::isRegistered('taskTemplate')) {
            return;
        }
        $keys = array_keys($this->lengthRestrictionDefaults);
        $taskTemplate = Zend_Registry::get('taskTemplate');
        $taskTemplateConf = $taskTemplate->lengthRestriction ?? null;
        foreach($keys as $key) {
            switch ($key) {
                case 'font':
                case 'fontSize':
                    $conf = $taskTemplateConf->pixelmapping->$key ?? null;
                    break;
                case 'maxNumberOfLines':
                    $conf = $taskTemplateConf->$key ?? null;
                    break;
                default:
                    $conf = $taskTemplateConf->$key ?? null;
                    break;
            }
            if(is_null($conf)) {
                continue;
            }
            $conf = trim($conf);
            if(empty($conf)) {
                continue;
            }
            $this->lengthRestrictionDefaults[$key] = $conf;
        }
    }
    
    /**
     * add the attributes needed for length restriction from transunit to segmentAttributes object
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     * @param array $unitAttributes
     * @param editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes
     */
    public function addAttributes(editor_Models_Import_FileParser_XmlParser $xmlparser, array $unitAttributes, editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes) {
        // Length-Restriction-Attributes (as set in xliff's trans-unit; fallback: task-template); optional
        $unit = $xmlparser->getAttribute($unitAttributes, 'size-unit', $this->lengthRestrictionDefaults['size-unit']);
        if($unit == 'char' || $unit == editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING) {
            foreach ($this->lengthRestrictionDefaults as $key => $defaultValue) {
                if(array_key_exists($key, self::$nonStandardXliffAttributes)) {
                    // non-standard xliff-attributes:
                    $nonStandardKeys = self::$nonStandardXliffAttributes[$key];
                    $keyInUnitAttributes = $nonStandardKeys['inUnit'];
                    $keyInSegmentAttributes = $nonStandardKeys['inSegmentAttr'];
                } else {
                    // standard xliff-attributes:
                    $keyInUnitAttributes = $key;
                    $keyInSegmentAttributes = $key;
                }
                $segmentAttributes->$keyInSegmentAttributes = $xmlparser->getAttribute($unitAttributes, $keyInUnitAttributes, $defaultValue);
            }
        }
        
        // When pixelMapping is to be used, the config's defaultPixelWidth for this fontSize must exist.
        // (We cannot assume that every character will have a pixelWidth set in the pixelMapping-table,
        // and if there is no pixelWidth set, the calculation of the pixelLength will be not reliable at all.)
        if ($segmentAttributes->sizeUnit != editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING) {
            return;
        }
        //throws an exception if no default values exist
        $this->pixelMapping->getDefaultPixelWidth($segmentAttributes->fontSize);
    }
}