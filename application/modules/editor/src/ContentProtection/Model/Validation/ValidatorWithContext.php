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

namespace MittagQI\Translate5\ContentProtection\Model\Validation;

use Zend_Validate_Abstract;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Validator_Abstract;

abstract class ValidatorWithContext extends ZfExtended_Models_Validator_Abstract
{
    /**
     * @var array<string, Zend_Validate_Abstract[]>
     */
    protected array $customFieldFilterInstances;

    public function __construct(ZfExtended_Models_Entity_Abstract $entity)
    {
        parent::__construct($entity);

        $this->defineContextValidators();
    }

    public function isValid(array $data)
    {
        $isValid = true;
        foreach ($data as $field => $value) {
            if (in_array($field, $this->dontValidateList)) {
                continue;
            }
            $this->checkUnvalidatedField($field);
            $isValid = $this->validateField($field, $value) && $isValid;
            $isValid = $this->walkCustomValidators($field, $value, $data) && $isValid;
        }

        return $isValid;
    }

    protected function walkCustomValidators($field, $value, $context = [])
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

            foreach ($this->customFieldFilterInstances[$field] as $instance) {
                $messages[] = $instance->getMessages();
            }
            $this->messages[$field] = array_merge($this->messages[$field] ?? [], ...$messages);
        }

        return $result;
    }

    protected function defineContextValidators(): void
    {
        foreach ($this->customFieldFilterInstances as $field => $instances) {
            foreach ($instances as $instance) {
                $this->addValidatorCustom(
                    $field,
                    fn ($value, $context) => $instance->isValid($value, $context), // @phpstan-ignore-line
                    true
                );
            }
        }
    }
}
