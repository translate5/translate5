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

namespace MittagQI\Translate5\ContentProtection\Model\Validation;

use Zend_Validate_Abstract;
use ZfExtended_Models_Entity_Abstract;

class OutputMappingValidator extends ValidatorWithContext
{
    /**
     * @var array<string, Zend_Validate_Abstract[]>
     */
    protected array $customFieldFilterInstances;

    public function __construct(ZfExtended_Models_Entity_Abstract $entity)
    {
        $this->customFieldFilterInstances = [
            'outputContentRecognitionId' => [new OutputRecognitionValidator()],
        ];
        parent::__construct($entity);
    }

    /**
     * Validators for Customer entity
     */
    protected function defineValidators()
    {
        //`id` int(11) NOT NULL AUTO_INCREMENT,
        $this->addValidator('id', 'int');

        $this->addValidator('languageId', 'int');
        $this->addValidator('inputContentRecognitionId', 'int');
        $this->addValidator('outputContentRecognitionId', 'int');
    }
}
