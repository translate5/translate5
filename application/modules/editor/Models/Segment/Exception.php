<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * General exception in segment processing. 
 */
class editor_Models_Segment_Exception extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.segment';
    
    protected static $localErrorCodes = [
        //Pixel Length codes:
        'E1081' => 'Textlength by pixel failed; most probably data about the pixelWidth is missing: fontFamily: "{fontFamily} fontSize: "{fontSize}".',
        'E1082' => 'Segment length calculation: missing pixel width for several characters.',
        'E1155' => 'Unable to save the segment. The segment model tried to save to the materialized view directly.',
    ];
}