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
      $this->addValidator('collectionId', 'int');
      // termEntryId ?
      $this->addValidator('language', 'stringLength', array('min' => 0, 'max' => 45));
      $this->addValidator('termId', 'int');
      $this->addValidator('termTbxId', 'stringLength', array('min' => 0, 'max' => 100));
      $this->addValidator('dataTypeId', 'int');
      $this->addValidator('type', 'stringLength', array('min' => 0, 'max' => 100));
      $this->addValidator('value', 'stringLength', array('min' => 0, 'max' => 65535));
      $this->addValidator('target', 'stringLength', array('min' => 0, 'max' => 100));
      $this->addValidator('isCreatedLocally', 'bool');
      $this->addValidator('createdBy', 'int');
      // createdAt ?
      $this->addValidator('updatedBy', 'int');
      // updatedAt ?
      $this->addValidator('termEntryGuid', 'stringLength', array('min' => 0, 'max' => 38));
      $this->addValidator('langSetGuid', 'stringLength', array('min' => 0, 'max' => 38));
      // termGuid ?
      $this->addValidator('guid', 'stringLength', array('min' => 0, 'max' => 38));
      $this->addValidator('elementName',  'stringLength', array('min' => 0, 'max' => 100));
      // attrLang ?

      $this->addValidator('name', 'stringLength', array('min' => 0, 'max' => 45)); // no such column
      $this->addValidator('entryId', 'stringLength', array('min' => 0, 'max' => 100)); // no such column
      //$this->addValidator('internalCount', 'int');
      //$this->addValidator('userGuid', 'stringLength', array('min' => 0, 'max' => 38));
      //$this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 38));
  }
}
