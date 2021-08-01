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

class editor_Models_Validator_CustomerConfiguration extends ZfExtended_Models_Validator_Abstract {
    
    /**
    * Validators for CustomerConfiguration entity
    */
    protected function defineValidators() {
        
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int');
        //`customerId` int(11) NOT NULL COMMENT 'Client (= id from table LEK_customer)',
        $this->addValidator('customerId', 'int');
        //`name` varchar(255) NOT NULL COMMENT 'corresponds to the old INI key',
        $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 255));
        //`confirmed` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'used for new values, 0 not confirmed by user, 1 confirmed',
        $this->addValidator('confirmed', 'boolean');
        //`module` varchar(100) DEFAULT NULL COMMENT 'the PHP module this config value was defined for',
        $this->addValidator('module', 'stringLength', array('min' => 0, 'max' => 100));
        //`category` varchar(100) NOT NULL DEFAULT 'other' COMMENT 'field to categorize the config values',
        $this->addValidator('category', 'stringLength', array('min' => 0, 'max' => 100));
        //`value` varchar(1024) DEFAULT NULL COMMENT 'the config value, if data exceeds 1024byte (especially for list and map) data should be stored in a own table',
        $this->addValidator('value', 'stringLength', array('min' => 0, 'max' => 1024));
        //`default` varchar(1024) DEFAULT NULL COMMENT 'the system default value for this config',
        $this->addValidator('default', 'stringLength', array('min' => 0, 'max' => 1024));
        //`defaults` varchar(1024) DEFAULT NULL COMMENT 'a comma separated list of default values, only one of this value is possible to be set by the GUI',
        $this->addValidator('defaults', 'stringLength', array('min' => 0, 'max' => 1024));
        //`type` enum('string','integer','boolean','list','map','absolutepath') NOT NULL DEFAULT 'string',
        
        //`description` varchar(1024) NOT NULL COMMENT 'contains a human readable description for what this config is for',
        $this->addValidator('description', 'stringLength', array('min' => 0, 'max' => 1024));
    }
}
