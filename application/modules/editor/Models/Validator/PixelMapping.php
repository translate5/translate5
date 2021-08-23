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

class editor_Models_Validator_PixelMapping extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for PixelMapping entity
    */
    protected function defineValidators() {
        // `id` int(11) AUTO_INCREMENT,
        $this->addValidator('id', 'int');
        // `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
        $this->addValidator('taskGuid', 'guid');
        // `fileId` int (11) NULL DEFAULT NULL COMMENT 'Foreign Key to LEK_files',
        $this->addValidator('fileId', 'int');
        // `font` VARCHAR (255) NOT NULL,
        $this->addValidator('font', 'stringLength', array('min' => 0, 'max' => 255));
        // `fontsize` int (3) NOT NULL,
        $this->addValidator('fontsize', 'int');
        // `unicodeChar` VARCHAR (4) NOT NULL COMMENT '(numeric)',
        $this->addValidator('unicodeChar', 'stringLength', array('min' => 2, 'max' => 4));
        // `pixelWidth` int (4) NOT NULL,
        $this->addValidator('pixelWidth', 'int');
    }
}
