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

class editor_Models_Validator_UserAssocDefault extends ZfExtended_Models_Validator_Abstract {
    protected function defineValidators() {
        $this->addValidator("id", "int");
        $this->addValidator("customerId", "int");
        $this->addValidator("sourceLang", "int");
        $this->addValidator("targetLang", "int");
        $this->addValidator("userGuid","stringLength", array("min" => 1, "max" => 38));
        $this->addValidator("workflowStepName","stringLength", array("min" => 1, "max" => 64));
        $this->addValidator("workflow","stringLength", array("min" => 1, "max" => 64));
        $this->addValidator("segmentrange","stringLength", array("min" => 0, "max" => 255));
        $this->addValidator("deadlineDate", "float",[],true);
    }
}
