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

use MittagQI\Translate5\ContentProtection\Model\Db\ContentRecognitionTable;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use Zend_Validate;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Abstract;

class ContentRecognitionValidator extends ValidatorWithContext
{
    public function __construct(ZfExtended_Models_Entity_Abstract $entity)
    {
        $this->customFieldFilterInstances = ['format' => [new FormatValidator()]];
        parent::__construct($entity);
    }

    /**
     * Validators for Customer entity
     */
    protected function defineValidators()
    {
        $table = ZfExtended_Factory::get(ContentRecognitionTable::class)->info(ContentRecognitionTable::NAME);
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $idValidator = new Zend_Validate();
        $idValidator->addValidator($this->validatorFactory('int'), true);
        // users should not be able neither create nor modify default records
        $idValidator->addValidator(new RecordIsNotDefaultValidator(['table' => $table, 'field' => 'id']), true);
        $this->addValidatorInstance('id', $idValidator);

        $this->addValidator('type', 'InArray', [NumberProtector::create()->types()]);
        //`name` varchar(255) NOT NULL,
        $this->addValidator('name', 'stringLength', ['min' => 3, 'max' => 255]);

        //`description` varchar(1024),
        $this->addValidator('description', 'stringLength', ['min' => 3, 'max' => 1024]);

        //`regex` varchar(255) NOT NULL,
        $regexValidator = new Zend_Validate();
        $regexValidator->addValidator($this->validatorFactory('stringLength', ['min' => 3, 'max' => 255]), true);
        $regexValidator->addValidator(new RegexPatternValidator(), true);

        $this->addValidatorInstance('regex', $regexValidator);

        $this->addValidator('matchId', 'int');

        //`format` varchar(255) NOT NULL,
        $this->addValidator('format', 'stringLength', ['min' => 0, 'max' => 255]);

        $this->addValidator('keepAsIs', 'boolean');
        $this->addValidator('enabled', 'boolean', allowNull: true);
    }

    public function isValid(array $data)
    {
        if (2 === count($data) && array_key_exists('enabled', $data)) {
            return is_bool($data['enabled']);
        }

        return parent::isValid($data);
    }
}
