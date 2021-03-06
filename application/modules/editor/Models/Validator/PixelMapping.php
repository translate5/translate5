<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
