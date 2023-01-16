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

namespace MittagQI\Translate5\LanguageResource;

use Zend_Db_Table_Abstract;
use ZfExtended_Models_Entity_Abstract;

class AssociationAbstract extends ZfExtended_Models_Entity_Abstract
{

    /***
     * @param string $taskGuid
     * @return array|null
     */
    public function loadTaskAssociated(string $taskGuid): ?array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?',$taskGuid);
        return $this->db->getAdapter()->fetchAll($s);
    }

    /***
     * Delete all associations for given taskGuid
     *
     * @param string $taskGuid
     * @return bool
     */
    public function deleteAllForTask(string $taskGuid): bool
    {
        return $this->db->delete(['taskGuid = ?' => $taskGuid]) > 0;
    }

    /***
     * Check if given resource is assigned to a task
     * @param int $resourceId
     * @param string $taskGuid
     * @return bool
     */
    public function isAssigned(int $resourceId, string $taskGuid): bool
    {
        $s = $this->db->select()
            ->where('taskGuid = ?',$taskGuid)
            ->where('languageResourceId = ?',$resourceId);
        return empty($this->db->getAdapter()->fetchAll($s)) === false;
    }

    /***
     * @param array $customers
     * @return array
     * @throws \Zend_Db_Table_Exception
     */
    public function getAssociatedByCustomer(array $customers, int $languageResourceId): array
    {
        $s = $this->db->select()
            ->from(['ta' => $this->db->info(Zend_Db_Table_Abstract::NAME)],['ta.*'])
            ->setIntegrityCheck(false)
            ->join(['t' => 'LEK_task'], 'ta.taskGuid = t.taskGuid',['t.taskName as taskName'])
            ->where('t.customerId IN(?)',$customers)
            ->where('ta.languageResourceId = ?',$languageResourceId);
        
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function deleteByIds(array $ids): bool
    {
        $cast = array_map('intval', $ids);
        return $this->db->delete(['id IN(?)' => $cast]) > 0;
    }

}