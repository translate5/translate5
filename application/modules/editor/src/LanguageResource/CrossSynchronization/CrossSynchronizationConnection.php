<?php

declare(strict_types=1);
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

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use Zend_Db_Table_Row_Abstract;
use ZfExtended_Models_Entity_Abstract;

/**
 * @method string getId()
 * @method void setId(int $id)
 * @method string getSourceLanguageResourceId()
 * @method void setSourceLanguageResourceId(int $id)
 * @method string getSourceType()
 * @method void setSourceType(string $type)
 * @method string getTargetLanguageResourceId()
 * @method void setTargetLanguageResourceId(int $id)
 * @method string getTargetType()
 * @method void setTargetType(string $type)
 * @method string getCustomerId()
 * @method void setCustomerId(int $customerId)
 */
class CrossSynchronizationConnection extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = Db\CrossSynchronizationConnection::class;

    protected $validatorInstanceClass = Validator\CrossLanguageResourceSynchronization::class;

    public function hydrate(array|Zend_Db_Table_Row_Abstract $data): void
    {
        $this->row = is_array($data) ? $this->db->createRow($data) : $data;
    }
}
