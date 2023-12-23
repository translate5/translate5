<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use Zend_Db_Statement_Exception;
use Zend_Db_Table_Row_Exception;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getTooltip()
 * @method void setTooltip(string $tooltip)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getPicklistData()
 * @method void setPicklistData(string $picklistData)
 * @method string getRegex()
 * @method void setRegex(string $regex)
 * @method string getMode()
 * @method void setMode(string $mode)
 * @method string getPlacesToShow()
 * @method void setPlacesToShow(string $placesToShow)
 * @method string getPosition()
 * @method void setPosition(string $position)
 */
class Field extends ZfExtended_Models_Entity_Abstract {

    /**
     * Db instance class
     *
     * @var string
     */
    protected $dbInstanceClass = \MittagQI\Translate5\Task\CustomFields\Db::class;

    protected $validatorInstanceClass = \MittagQI\Translate5\Task\CustomFields\Validator::class;
}