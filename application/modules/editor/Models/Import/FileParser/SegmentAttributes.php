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
 * Segment Attributes
 * This class is just used as datatype struct in the import fileparser to have IDE completion here
 * if we have new attrbiutes in the core code, they should be added here.
 * For Plugin purposes and flexible extension there exist the customMetaAttributes array as dynamic container
 */
class editor_Models_Import_FileParser_SegmentAttributes {
    /**
     * for XLF derivates there can be <mrk mtype="seg" mid=""> segments, this mid is stored here
     * @var integer
     */
    public $mrkMid = 0;
    
    /**
     * the segments matchrate, defaults to 0
     * @var integer
     */
    public $matchRate = 0;
    
    /**
     * the segments matchrate type, defaults to 'import'
     * @var string
     */
    public $matchRateType = 'import'; //FIXME make me to come from MatchRateType class
    
    /**
     * flag if the segments was autopropagated or not, defaults to false
     * @var boolean
     */
    public $autopropagated = false;
    
    /**
     * flag if segment was locked in file
     * @var boolean
     */
    public $locked = false;
    
    
    /**
     * pretranslated state of the segment, calculated by the fileparser
     * @var boolean
     */
    public $isPreTranslated = false;
    
    /**
     * autostateid of the segment, calculated by the fileparser
     * @var integer
     */
    public $autoStateId;
    
    /**
     * Is the segment editable or not, calculated by the fileparser
     * @var boolean
     */
    public $editable;
    
    /**
     * Stores the info if the segment was translated or not (empty target)
     * @var boolean
     */
    public $isTranslated;
    
    /**
     * Stores some state information about the target segment
     * @var string
     */
    public $targetState;
    
    /**
     * Min Width of a segment (characters or pixel)
     * @var integer
     */
    public $minWidth = null;
    
    /**
     * Max Width of a segment (characters or pixel)
     * @var integer
     */
    public $maxWidth = null;
    
    /**
     * Max. number of lines in a segment (used for pixel-based length check only)
     * @var integer
     */
    public $maxNumberOfLines = null;
    
    /**
     * Size-unit of a segment (size-unit="char" or "pixel"; default if nothing is set: "pixel")
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#size-unit
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#maxwidth
     * @var string
     */
    public $sizeUnit = null;
    
    /**
     * Font-family for pixel-based length of a segment
     * @var string
     */
    public $font = null;
    
    /**
     * Font-size for pixel-based length of a segment
     * @var integer
     */
    public $fontSize = null;
    
    /**
     * Additional string length in transunit before first mrk tag, to be added to the length calculation of the segment once
     * @var integer
     */
    public $additionalUnitLength = 0;
    
    /**
     * Additional string length of the string between the mrk tag containing that segment and next mrk, or the length of the content after the last mrk tag
     * This value must be added to the calculated length of each segment on each segment update and on the fly in the frontend
     * @var integer
     */
    public $additionalMrkLength = 0;
    
    /**
     * The transunitId and fileId (or in general group id) if some segments are belonging together.
     * For example in XLF there is one transunit with multiple mrk tags.
     * Each MRK tag is one segment in translate5, through the transunitId the segments are grouped.
     * The value is fileId_transunitId
     *
     * @var string
     */
    public $transunitId;
    
    /**
     * For Plugin purposes and flexibility additional meta attributes for the segment can be placed in this assoc array.
     * key = meta field name, value = value.
     * new attributes for the core import functionality should get an own attribute
     * @var array
     */
    public $customMetaAttributes = [];
}