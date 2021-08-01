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