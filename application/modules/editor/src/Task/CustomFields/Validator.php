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
namespace MittagQI\Translate5\Task\CustomFields;

use ZfExtended_Models_Validator_Abstract;

class Validator extends ZfExtended_Models_Validator_Abstract
{

    /**
     * @return void
     * @throws \Zend_Exception
     */
    protected function defineValidators(): void
    {
        // creates validators from the above table definition
        // allowNull is set to true, because the default values are resolved on db level
        $this->addValidator('id', 'int', allowNull: true);
        $this->addValidator('label', 'stringLength', ['min' => 0, 'max' => 255], allowNull: true);
        $this->addValidator('tooltip', 'stringLength', ['min' => 0, 'max' => 255], allowNull: true);
        $this->addValidator('type', 'inArray', [['text', 'textarea', 'boolean', 'picklist']], allowNull: true);
        $this->addValidator('picklistData', 'stringLength', ['min' => 0, 'max' => 65535], allowNull: true);
        $this->addValidator('regex', 'stringLength', ['min' => 0, 'max' => 255], allowNull: true);
        $this->addValidator('mode', 'inArray', [['optional', 'required', 'readonly']], allowNull: true);
        $this->addValidatorCustom('placesToShow', function ($value) {
            if (empty($value)) {
                return true;
            }
            if(is_string($value)) {
                $value = explode(',', $value);
            }
            $places = array_map('trim', $value);
            foreach ($places as $place) {
                if (!in_array($place, ['projectWizard', 'projectGrid', 'taskGrid'])) {
                    return false;
                }
            }
            return true;
        }, allowNull: true);
        $this->addValidator('position', 'int', allowNull: true);
    }
}