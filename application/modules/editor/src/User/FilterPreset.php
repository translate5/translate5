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

namespace MittagQI\Translate5\User;

use editor_Models_Config as Config;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_Abstract;

/**
 * User Filter Preset Entity Object
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getPanel()
 * @method void setPanel(string $panel)
 * @method string getState()
 * @method void setState(string $state)
 */
class FilterPreset extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = Db\FilterPreset::class;

    protected $validatorInstanceClass = Validator\FilterPreset::class;

    /**
     * Load filter presets for a given $userId, but grouped by panel
     *
     * @throws \Zend_Db_Statement_Exception|\ReflectionException
     */
    public function loadByUserIdGroupedByPanel(int $userId): array
    {
        // Get array of possible panels for which presets may exist
        $possible = $this->db->info($this->db::METADATA)['panel']['DATA_TYPE'];
        $possible = preg_replace('~^enum\(|\'|\)$~', '', $possible);
        $possible = explode(',', $possible);

        // Get actually existing presets for a user, identified by $userId
        $existing = $this->db->getAdapter()->query("
            SELECT `panel`, `id`, `title`, `state`
            FROM `LEK_user_filter_preset`
            WHERE `userId` = ? 
            ORDER BY `id`
        ", $userId)->fetchAll(\PDO::FETCH_GROUP);

        // Get config class instance
        $config = Factory::get(Config::class);

        // Foreach possible panel
        foreach ($possible as $panel) {
            // If at least on preset exists for the $panel
            if (isset($existing[$panel])) {
                // Get current filters state
                $state = json_decode(
                    $config->loadAllMerged("runtimeOptions.frontend.defaultState.editor.$panel")[0]['value']
                )->storeState->filters;

                // Check if current filters state match some preset's filters state,
                // and if yes - make sure that preset will be selected in the UI combo
                $selected = 0;
                foreach ($existing[$panel] as $preset) {
                    $presetState = json_decode($preset['state'])->storeState->filters;
                    if (serialize($state) === serialize($presetState)) {
                        $selected = $preset['id'];
                    }
                }

                // Else if no presets exists so far for the $panel
            } else {
                // Setup empty store
                $existing[$panel] = [];
                $selected = 0;
            }

            // Wrap into additional array that will contain not only
            // existing presets store but the selected value as well, if any
            $existing[$panel] = [
                'store' => $existing[$panel],
                'value' => $selected,
            ];
        }

        // Return data sufficient to be fed to presets comboboxes in UI
        return $existing;
    }
}
