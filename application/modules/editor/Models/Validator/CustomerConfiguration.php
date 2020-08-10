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
