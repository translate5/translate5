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

class editor_Models_Validator_Term_Attribute extends ZfExtended_Models_Validator_Abstract {
  
  /**
   * Validators for Term Attributes
   */
  protected function defineValidators() {
    //comment = string, without length contrain. No validator needed / possible 
    $this->addValidator('id', 'int');
    $this->addValidator('labelId', 'int');
    $this->addValidator('collectionId', 'int');
    $this->addValidator('termId', 'int');
    $this->addValidator('parentId', 'int');
    $this->addValidator('language', 'stringLength', array('min' => 0, 'max' => 45));
    $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 45));
    $this->addValidator('attrType', 'stringLength', array('min' => 0, 'max' => 100));
    $this->addValidator('attrTarget', 'stringLength', array('min' => 0, 'max' => 100));
    $this->addValidator('attrId', 'stringLength', array('min' => 0, 'max' => 100));
    $this->addValidator('attrLang', 'stringLength', array('min' => 0, 'max' => 45));
    $this->addValidator('value', 'stringLength', array('min' => 0, 'max' => 65535));
    $this->addValidator('userGuid', 'stringLength', array('min' => 0, 'max' => 38));
    $this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 255));
    $this->addValidator('processStatus', 'stringLength', array('min' => 0, 'max' => 128));
  }
}