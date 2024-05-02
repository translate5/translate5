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
 * General exception in segment processing.
 */
class editor_Models_Segment_Exception extends ZfExtended_ErrorCodeException
{
    /**
     * @var string
     */
    protected $domain = 'editor.segment';

    protected static $localErrorCodes = [
        //Pixel Length codes:
        'E1081' => 'Textlength by pixel failed; most probably data about the pixelWidth is missing: fontFamily: "{fontFamily} fontSize: "{fontSize}" character: "{char} ({charCode})".',
        'E1082' => 'Segment length calculation: missing pixel width for several characters.',
        'E1155' => 'Unable to save the segment. The segment model tried to save to the materialized view directly.',
        'E1343' => 'Setting the FieldTags tags by text led to a changed text-content presumably because the encoded tags have been improperly processed'
    ];
}
