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

declare(strict_types=1);

namespace MittagQI\Translate5\User\Validation;

use Zend_Validate;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Validator_User;

class UserValidator extends ZfExtended_Models_Validator_User
{
    private array $customValidatorInstances = [];

    public function __construct(ZfExtended_Models_Entity_Abstract $entity)
    {
        $this->customValidatorInstances['roles'] = [
            new RolesValidator(),
        ];

        parent::__construct($entity);
    }

    protected function defineValidators(): void
    {
        parent::defineValidators();

        foreach ($this->customValidatorInstances as $field => $validatorInstances) {
            $validate = new Zend_Validate();
            foreach ($validatorInstances as $validatorInstance) {
                $validate->addValidator($validatorInstance, true);
            }

            $this->addValidatorCustom(
                $field,
                fn ($value) => $validate->isValid($value),
                true
            );
        }
    }

    protected function walkCustomValidators($field, $value, $context = []): bool
    {
        $result = true;
        if (empty($this->customValidators[$field])) {
            return $result;
        }

        foreach ($this->customValidators[$field] as $method) {
            $result = $method($value, $context) && $result;
        }

        if (! $result) {
            $messages = [];

            foreach ($this->customValidatorInstances[$field] as $instance) {
                $messages[] = $instance->getMessages();
            }
            $this->messages[$field] = array_merge($this->messages[$field] ?? [], ...$messages);
        }

        return $result;
    }
}