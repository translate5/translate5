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

class editor_Models_Validator_TaskUserTracking extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for TaskUserTracking Entity
     */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int');
        //`taskGuid` varchar(38) NOT NULL,
        $this->addValidator('taskGuid', 'stringLength', array('min' => 0, 'max' => 38));
        //`userGuid` varchar(38) NOT NULL,
        $this->addValidator('userGuid', 'stringLength', array('min' => 0, 'max' => 38));
        //`taskOpenerNumber` int(3) NOT NULL,
        $this->addValidator('taskOpenerNumber', 'int');
        //`firstName` varchar(255) NOT NULL,
        $this->addValidator('firstName', 'stringLength', array('min' => 0, 'max' => 255));
        //`surName` varchar(255) NOT NULL,
        $this->addValidator('surName', 'stringLength', array('min' => 0, 'max' => 255));
        //`userName` varchar(255) NOT NULL,
        $this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 255));
        //`role` varchar(60) NOT NULL,
        $this->addValidator('role', 'stringLength', array('min' => 0, 'max' => 60));
        
    }
}
