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

/**
 * MatchAnalysis TaskAssoc Entity Object
 *
 * @method string getId()
 * @method void setId(int $id)
 *
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 *
 * @method string getUuid()
 * @method void setUuid(string $taskUuid)
 *
 * @method string getInternalFuzzy()
 * @method void setInternalFuzzy(bool $internalFuzzy)
 *
 * @method string getPretranslateMatchrate()
 * @method void setPretranslateMatchrate(int $pretranslateMatchrate)
 *
 * @method string getCreated()
 * @method void setCreated(string $created)
 *
 * @method string getFinishedAt()
 * @method void setFinishedAt(string $finishedAt)
 */
class editor_Plugins_MatchAnalysis_Models_TaskAssoc extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_TaskAssoc';

    protected $validatorInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Validator_TaskAssoc';

    /***
     * Load the newest analysis for given taskGuid
     * @param string $taskGuid
     * @return array|NULL
     */
    public function loadNewestByTaskGuid($taskGuid): ?array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)->order('id DESC')->limit(1);
        $row = $this->db->fetchRow($s);
        if (! $row) {
            return null;
        }

        return $row->toArray();
    }

    /**
     * Sets the finishedAt date to database now
     */
    public function finishNow(int $errorCount): void
    {
        $id = (int) $this->getId();
        $this->db->update([
            'errorCount' => $errorCount,
            'finishedAt' => new Zend_Db_Expr('now()'),
        ], 'id = ' . $id);
    }
}
