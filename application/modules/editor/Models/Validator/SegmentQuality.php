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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Models_Validator_SegmentQuality extends ZfExtended_Models_Validator_Abstract {
    protected function defineValidators() {
        $this->addValidator('id', 'int');
        $this->addValidator('segmentId', 'int');
        $this->addValidator('taskGuid','stringLength', array('min' => 1, 'max' => 38));
        $this->addValidator('field','stringLength', array('min' => 1, 'max' => 300));
        $this->addValidator('type','stringLength', array('min' => 1, 'max' => 10));
        $this->addValidator('category','stringLength', array('min' => 0, 'max' => 50));
        $this->addValidator('startIndex', 'int');
        $this->addValidator('endIndex', 'int');
        $this->addValidator('falsePositive', 'int');
        $this->addValidator('msgkey','stringLength', array('min' => 0, 'max' => 64));
        $this->addValidator('qmtype', 'int');
        $this->addValidator('severity','stringLength', array('min' => 0, 'max' => 255));
    }
}
