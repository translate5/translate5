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

use MittagQI\Translate5\Test\ApiTestAbstract;
use stdClass;
use Throwable;
use Zend_Registry;
use ZfExtended_Models_Worker;
use ZfExtended_Plugin_Manager;

/**
 * This class implements all direct calls to the DB the tests run on during the tests
 * Directly accessing the DB when in API-test generally is unwanted as this potentially causes trouble with
 * file-writes, entity-versions, etc. On the other hand, endpoints to cleanup workes or activate/deactivate plugins are
 * a potential security problem we should avoid
 */
final class DbHelper
{
    /**
     * Tries to activate the passed plugins
     */
    public static function activatePlugins(array $pluginClasses): bool
    {
        return self::disableOrEnablePlugins($pluginClasses, true);
    }

    /**
     * Tries to deactivate the passed plugins
     */
    public static function deactivatePlugins(array $pluginClasses): bool
    {
        return self::disableOrEnablePlugins($pluginClasses, false);
    }

    /**
     * Activates/deactivates a bunch of plugins. returns the success of the action
     */
    private static function disableOrEnablePlugins(array $pluginClasses, bool $activate): bool
    {
        $success = true;

        try {
            $pluginmanager = Zend_Registry::get('PluginManager');
            /* @var $pluginmanager ZfExtended_Plugin_Manager */
            foreach ($pluginClasses as $pluginClass) {
                $plugin = $pluginmanager::getPluginNameByClass($pluginClass);
                if (! $pluginmanager->setActive($plugin, $activate)) {
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
     */
    public static function cleanupWorkers(
        bool $forceRemoval = false,
        bool $preventRemoval = false,
        bool $addRemainingWorkerTypes = false,
    ): stdClass {
        $result = new stdClass();
        $result->cleanupNeccessary = false;
        $worker = new ZfExtended_Models_Worker();
        $summary = $worker->getSummary();
        $numFaulty =
            $summary[ZfExtended_Models_Worker::STATE_SCHEDULED]
            + $summary[ZfExtended_Models_Worker::STATE_WAITING]
            + $summary[ZfExtended_Models_Worker::STATE_RUNNING]
            + $summary[ZfExtended_Models_Worker::STATE_DEFUNCT];
        $result->cleanupNeccessary = ($numFaulty > 0);

        if ($result->cleanupNeccessary && $addRemainingWorkerTypes) {
            $result->remainingWorkers = $worker->getRemainingWorkerInfo();
        }
        if (! $preventRemoval && ($numFaulty > 0 || $forceRemoval)) {
            // for the following tests to function properly running or dead workers are unwanted
            $worker->db->delete('1 = 1');
        }
        $result->worker = $summary;

        return $result;
    }

    /**
     * Removes all existing workers from the DB
     */
    public static function removeWorkers()
    {
        $worker = new ZfExtended_Models_Worker();
        $worker->db->delete('1 = 1');
    }

    public static function getLastWorkerId(): int
    {
        $worker = new ZfExtended_Models_Worker();
        $s = $worker->db->select()->from($worker->db, ['id'])->order('id DESC')->limit(1);
        $row = $worker->db->fetchRow($s);

        return $row['id'] ?? 0;
    }

    public static function getLastWorkers(int $sinceId, string $workerClass, array $taskGuids = []): array
    {
        $worker = new ZfExtended_Models_Worker();
        $s = $worker->db->select()
            ->where('id > ?', $sinceId)
            ->where('worker = ?', $workerClass);
        if (count($taskGuids) > 0) {
            $s->where('taskGuid IN (?)', $taskGuids);
        }

        return $worker->db->fetchAll($s)->toArray();
    }

    /**
     * This function waits for one anonymous or 1 to n known workers (identified by taskGuid) to complete
     * @param ApiTestAbstract $test The Currently running test
     * @param string $class The worker-class to check. CRUCIAL: these must be "highlander"-workers that have only a
     *     single instance per run
     * @param array $taskGuids If given can identify one or multiple tasks identifying the worker. In this case the
     *     waiting lasts, until ALL identified workers are finished
     * @param bool $failOnError defines, if the test shall fail if the worker fails
     * @param int $timeout The max. runtime of the waiting
     * @param array $waitForStates The state we need to wait fore, usually DONE
     * @param array $failStates The state we break & fail, usually DEFUNCT
     */
    public static function waitForWorkers(
        ApiTestAbstract $test,
        string $class,
        array $taskGuids = [],
        bool $failOnError = true,
        int $timeout = 100,
        array $waitForStates = [ZfExtended_Models_Worker::STATE_DONE],
        array $failStates = [ZfExtended_Models_Worker::STATE_DEFUNCT],
    ): void {
        error_log("waitForWorkers: " . get_class($test) . " / $class / $failOnError / $timeout");

        $numTaskGuids = count($taskGuids);
        for ($counter = 0; $counter < $timeout; $counter++) {
            sleep(1);
            $foundWorkes = self::getLastWorkers($test::getLastWorkerId(), $class, $taskGuids);
            $numFinished = 0;
            $numNotAsExpected = 0;
            foreach ($foundWorkes as $worker) {
                if (in_array($worker['state'], $waitForStates)) {
                    $numFinished++;
                } else {
                    $numNotAsExpected++;
                }
                if (in_array($worker['state'], $failStates)) {
                    if ($failOnError) {
                        $test->fail('Worker defunct: ID: ' . $worker['id'] . ' ' . $worker['worker']);
                    }

                    return;
                }
            }
            // when there were no workers found that are not as expected ... but they are less than taskGuids
            // that means, done-workers may have already been cleaned up
            if ($numNotAsExpected === 0 && $numFinished < $numTaskGuids) {
                error_log('All found workers are in the expected state'
                    . ' but we could not find workers for all the given task-guids');

                return;
            }
            // we finish when all "known" workers are finished or a single "any" worker of the type is finished ...
            if (($numTaskGuids > 0 && $numTaskGuids === $numFinished) || ($numTaskGuids === 0 && $numFinished === 1)) {
                return;
            }
            if ($counter % 5 == 0) {
                error_log('Worker state check ' . $counter . '/' . $timeout . ' [' . get_class($test) . ']');
            }
        }
        $test->fail('Worker not added/finished in ' . $timeout . ' seconds: ' . $class);
    }
}
