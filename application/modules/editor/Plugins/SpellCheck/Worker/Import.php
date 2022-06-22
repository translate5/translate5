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
 * The Worker to process the termtagging for an import
 */
class editor_Plugins_SpellCheck_Worker_Import extends editor_Plugins_SpellCheck_Worker_Abstract {

    /**
     * Resource pool key
     *
     * @var string
     */
    protected $resourcePool = 'import';
    
    /***
     * Spell checking takes approximately 15 % of the import time
     *
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int {
        return 15;
    }

    /**
     * Load next bunch of segments to be process
     *
     * @param string $slot
     * @return array
     */
    protected function loadNextSegments(string $slot): array
    {
        // Load segments to be processed
        $result = parent::loadNextSegments($slot);

        // If nothing left
        if (empty($result)) {

            /* @var $db editor_Models_Db_SegmentMeta */
            $db = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');

            // Get quantities-by-spellcheckState
            $sql = $db->select()
                ->from($db, ['spellcheckState', 'cnt' => 'count(id)'])
                ->where('taskGuid = ?', $this->task->getTaskGuid());
            $segmentCounts = $db->fetchAll($sql)->toArray();
            $data = array_column($segmentCounts, 'cnt', 'spellcheckState');

            // Convert to human-readable log format
            $data = join(', ', array_map(function ($v, $k) { return sprintf("%s: '%s'", $k, $v); }, $data, array_keys($data)));

            /*class_exists('editor_Utils');
            i('empty result - ' . date('H:i:s') . ' - ' . $slot . ' - ' . getmypid() , 'a');
            i(stack(), 'a');*/

            // Log we're done
            $this->getLogger()->info('E1364', 'SpellCheck overall run done - {segmentCounts}', [
                'task' => $this->task,
                'segmentCounts' => $data,
            ]);
        /*} else {
            class_exists('editor_Utils');
            i('non empty result - ' . date('H:i:s') . ' - '. $slot . ' - ' . getmypid() , 'a');
            i(stack(), 'a');*/
        }

        // Return bunch
        return $result;
    }
}
