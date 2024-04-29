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

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Segment;
use editor_Services_ServiceResult;
use ZfExtended_Models_Entity_Abstract;

/**
 * @method string getId()
 * @method void setId(int $id)
 * @method string getLanguageResource()
 * @method void setLanguageResource(int $languageResource)
 * @method string getSegmentId()
 * @method void setSegmentId(int $segmentId)
 * @method string getResult()
 * @method void setResult(string $result)
 */
class BatchResult extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'MittagQI\Translate5\LanguageResource\Pretranslation\Db\BatchResult';

    protected $validatorInstanceClass = 'MittagQI\Translate5\LanguageResource\Pretranslation\Validator\BatchResult';

    /**
     * Load the latest service result cache for segment, langauge resource and taskGuid
     * @param string $segmentId
     * @param int $languageResource
     * @return editor_Services_ServiceResult
     */
    public function getResults(string $segmentId, int $languageResource): editor_Services_ServiceResult
    {
        $s = $this->db->select()
            ->where('segmentId = ?', $segmentId)
            ->where('languageResource = ?', $languageResource)
            ->order('id desc')
            ->limit(1);
        $result = $this->db->fetchAll($s)->toArray();
        if (empty($result)) {
            return new editor_Services_ServiceResult();
        }
        $result = reset($result);

        return unserialize($result['result']);
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Table_Exception
     */
    public function saveResult(int $segmentId, int $languageResourceId, string $taskGuid, string $result): void
    {
        $adapter = $this->db->getAdapter();

        $adapter->insert($this->db->info($this->db::NAME), [
            'segmentId' => $segmentId,
            'languageResource' => $languageResourceId,
            'taskGuid' => $taskGuid,
            'result' => $result,
        ]);

    }

    /**
     * Delete all cached records for given language resources
     * @param array $languageResources
     * @param string $taskGuid
     * @return int
     */
    public function deleteForLanguageresource(array $languageResources,string $taskGuid): int
    {
        if (empty($languageResources)) {
            return 0;
        }

        return $this->db->delete([
            'languageResource IN(?)' => $languageResources,
            'taskGuid = ?' => $taskGuid,
        ]);
    }

    /**
     * Remove cache records older than one day
     * @return int
     */
    public function deleteOlderRecords(): int
    {
        return $this->db->delete([
            'timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)',
        ]);
    }
}
