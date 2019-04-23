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
 * Should be used if a segment request to the server contains data which is not valid
 * points to HTTP 422 Unprocessable Entity
 */
class editor_Models_Segment_UnprocessableException extends ZfExtended_UnprocessableEntity {
    /**
     * @var string
     */
    protected $domain = 'editor.segment';
    
    protected static $localErrorCodes = [
        'E1065' => 'The data of the saved segment is not valid.',
        // ensure that E1066 produces an warning instead only an info or debug!
        'E1066' => 'The data of the saved segment is not valid. The segment content is either to long or to short.',
    ];
    
    /**
     * @param string $errorCode
     * @param array $extra
     * @param Exception $previous
     */
    public function __construct($errorCode, array $extra = [], Exception $previous = null) {
        parent::__construct($errorCode, $extra, $previous);
        //if the length is not correct, this should produce an warning in the log instead
        if($errorCode == 'E1066') {
            $this->level = ZfExtended_Logger::LEVEL_WARN;
        }
    }
}