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
 * 
 * The Worker to process the termtagging for a import or a subsequent analysis
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends editor_Plugins_TermTagger_Worker_Abstract {

    protected $resourcePool = 'import';
    
    /***
     * Term tagging takes approximately 15 % of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight() {
        return 15;
    }

    protected function loadNextSegments(string $slot): array
    {
        $result = parent::loadNextSegments($slot); // TODO: Change the autogenerated stub
        if(empty($result)) {

            $db = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
            /* @var $db editor_Models_Db_SegmentMeta */

            $sql = $db->select()
                ->from($db, ['termtagState', 'cnt' => 'count(id)'])
                ->where('taskGuid = ?', $this->task->getTaskGuid());
            $segmentCounts = $db->fetchAll($sql)->toArray();

            $data = array_column($segmentCounts, 'cnt', 'termtagState');
            $data = join(', ', array_map(function ($v, $k) { return sprintf("%s: '%s'", $k, $v); }, $data, array_keys($data)));
            $this->getLogger()->info('E1364', 'TermTagger overall run done - {segmentCounts}', [
                'task' => $this->task,
                'segmentCounts' => $data,
            ]);
        }
        return $result;
    }
}
