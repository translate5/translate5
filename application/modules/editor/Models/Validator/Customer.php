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

class editor_Models_Validator_Customer extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for Customer entity
    */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int');
        
        //`name` varchar(255) NOT NULL DEFAULT '',
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 255));
        
        //`number` varchar(255) DEFAULT NULL,
        $this->addValidator('number', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('searchCharacterLimit', 'int');
        
        $this->addValidator('domain', 'stringLength', array('min' => 0, 'max' => 255),true);
        
        $this->addValidator('openIdServer', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('openIdIssuer', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('openIdServerRoles', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('openIdDefaultServerRoles', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('openIdAuth2Url', 'stringLength', array('min' => 0, 'max' => 255));
        
        $this->addValidator('openIdClientId', 'stringLength', array('min' => 0, 'max' => 1024));
        
        $this->addValidator('openIdClientSecret', 'stringLength', array('min' => 0, 'max' => 1024));
        
        $this->addValidator('openIdRedirectLabel', 'stringLength', array('min' => 0, 'max' => 1024));
        
        $this->addValidator('openIdRedirectCheckbox', 'int');
        
    }
}
