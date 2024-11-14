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

namespace MittagQI\Translate5\Segment\Db;

use Zend_Db_Table_Row;

/**
 * Overwritten just to make the modified-fields object accessible
 *
 * @property string $segmentId
 * @property string $taskGuid
 * @property string $tagsJson
 * @property string $processing
 */
final class ProcessingRow extends Zend_Db_Table_Row
{
    protected $_tableClass = Processing::class;

    /**
     * Catches if there are unsaved updates
     */
    public function isDirty(): bool
    {
        return ! empty($this->_modifiedFields);
    }

    /**
     * This is rather a Hack to simulate a successfully updated row
     */
    public function mimicStateUpdate(string $column, int $state): void
    {
        $this->_data[$this->_transformColumn($column)] = $state;
        $this->_data[$this->_transformColumn('processing')] = 1;
    }
}
