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

namespace MittagQI\Translate5\Test\Api;

use stdClass;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;
use ZfExtended_Plugin_Manager;

/**
 * This class implements all direct calls to the DB the tests run on during the tests
 * Directly accessing the DB when in API-test generally is unwanted as this potentially causes trouble with file-writes, entity-versions, etc.
 * On the other hand, endpoints to cleanup workes or activate/deactivate plugins are a potential security problem we should avoid
 */
final class DbHelper
{
    /**
     * Tries to activate the passed plugins
     * @param array $pluginClasses
     * @return bool
     */
    public static function activatePlugins(array $pluginClasses): bool
    {
        return self::disableOrEnablePlugins($pluginClasses, true);
    }

    /**
     * Tries to deactivate the passed plugins
     * @param array $pluginClasses
     * @return bool
     */
    public static function deactivatePlugins(array $pluginClasses): bool
    {
        return self::disableOrEnablePlugins($pluginClasses, false);
    }

    /**
     * Activates/deactivates a bunch of plugins. returns the success of the action
     * @param array $pluginClasses
     * @param bool $activate
     * @return bool
     */
    private static function disableOrEnablePlugins(array $pluginClasses, bool $activate): bool
    {
        $success = true;
        try {
            $pluginmanager = Zend_Registry::get('PluginManager');
            /* @var $pluginmanager ZfExtended_Plugin_Manager */
            foreach ($pluginClasses as $pluginClass) {
                $plugin = $pluginmanager::getPluginNameByClass($pluginClass);
                if (!$pluginmanager->setActive($plugin, $activate)) {
                    $success = false;
                }
            }
        } catch (Throwable $e) {
            $success = false;
        }
        return $success;
    }

    /**
     * Checks the workers a test leaves in the DB for active ones. Cleans that up according the given params
     * @param bool $forceRemoval: if set, the worker-db will be cleaned even if no non-done workers remained
     * @param bool $preventRemoval: if set, the worker-table will not be cleaned no matter what
     * @param bool $addRemainingWorkerTypes: if set, the unique list of remaining worrkers will be added
     * @return stdClass
     */
    public static function cleanupWorkers(bool $forceRemoval = false, bool $preventRemoval = false, bool $addRemainingWorkerTypes = false): stdClass
    {
        $result = new stdClass();
        $result->cleanupNeccessary = false;
        $worker = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);
        $summary = $worker->getSummary();
        $numFaulty =
            $summary[ZfExtended_Models_Worker::STATE_SCHEDULED]
            + $summary[ZfExtended_Models_Worker::STATE_WAITING]
            + $summary[ZfExtended_Models_Worker::STATE_RUNNING]
            + $summary[ZfExtended_Models_Worker::STATE_DEFUNCT];
        $result->cleanupNeccessary = ($numFaulty > 0);

        if($result->cleanupNeccessary && $addRemainingWorkerTypes){
            $result->remainingWorkers = $worker->getRemainingWorkerInfo();
        }
        if (!$preventRemoval && ($numFaulty > 0 || $forceRemoval)) {
            // for the following tests to function properly running or dead workers are unwanted
            $worker->db->delete('1 = 1');
        }
        $result->worker = $summary;
        return $result;
    }

    /**
     * Removes all existing workers from the DB
     * @return void
     */
    public static function removeWorkers()
    {
        $worker = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);
        $worker->db->delete('1 = 1');
    }
}
