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

        $this->addValidatorCustom('label', function ($value) {
            if (empty($value) || strlen($value) > 255) {
                $this->addMessage(
                    'label',
                    'invalidLabel',
                    'The label must be a string with a maximum length of 255 characters'
                );
                return false;
            }
            try {
                $decoded = json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addMessage(
                        'label',
                        'invalidLabel',
                        'Error on decoding the label JSON string'
                    );
                    return false;
                }
                foreach ($decoded as $label) {
                    if (empty($label)) {
                        $this->addMessage(
                            'label',
                            'invalidLabel',
                            'The label must be a valid JSON string and all values must be non empty strings'
                        );
                        return false;
                    }
                }
            } catch (\Zend_Exception $e) {
                $this->addMessage(
                    'label',
                    'invalidLabel',
                    'The label must be a valid JSON string');
                return false;
            }

            return true;
        }, allowNull: true);

        $this->addValidator('tooltip', 'stringLength', ['min' => 0, 'max' => 255], allowNull: true);
        $this->addValidator('type', 'inArray', [['textfield', 'textarea', 'checkbox', 'combobox']], allowNull: true);
        $this->addValidator('comboboxData', 'stringLength', ['min' => 0, 'max' => 65535], allowNull: true);
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