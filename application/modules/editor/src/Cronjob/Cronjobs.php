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

declare(strict_types=1);

namespace MittagQI\Translate5\Cronjob;

use Bootstrap;
use editor_Workflow_Exception;
use editor_Workflow_Manager;
use MittagQI\Translate5\Logging\Rotation;
use Zend_Application_Bootstrap_Exception as Zend_Application_Bootstrap_ExceptionAlias;
use ZfExtended_Factory;
use ZfExtended_Logger_Summary;
use ZfExtended_Resource_GarbageCollector;

class Cronjobs
{
    private static bool $running = false;

    public function __construct(
        private Bootstrap $bootstrap,
        private CronEventTrigger $eventTrigger
    ) {
    }

    /**
     * returns true if current request is executed in a cron job
     */
    public static function isRunning(): bool
    {
        return self::$running;
    }

    /**
     * @throws editor_Workflow_Exception
     * @throws Zend_Application_Bootstrap_ExceptionAlias
     */
    public function periodical(): void
    {
        //FIXME exception handling here, encaps each "job" and log exception, otherwise it just bubbles to the CLI output
        self::$running = true;
        /* @var $gc ZfExtended_Resource_GarbageCollector */
        $gc = $this->bootstrap->getPluginResource(ZfExtended_Resource_GarbageCollector::class);
        $gc->cleanUp($gc::ORIGIN_CRON);
        $this->doCronWorkflow('doCronPeriodical');
        $this->eventTrigger->triggerPeriodical();
    }

    /**
     * @throws editor_Workflow_Exception
     */
    public function daily(): void
    {
        self::$running = true;
        $this->doCronWorkflow('doCronDaily');

        //FIXME should come configurable from workflow action table
        // + additional receivers, independant from sys admin users
        //$summary = ZfExtended_Factory::get(ZfExtended_Logger_Summary::class);
        //$summary->sendSummaryToAdmins();

        $log = ZfExtended_Factory::get(\ZfExtended_Models_Log::class);
        $log->purgeOlderAs(\Zend_Registry::get('config')->runtimeOptions?->logger?->keepWeeks ?? 6);
        $this->eventTrigger->triggerDaily();

        // Rotate logs
        $this->rotateLogs();
    }

    /**
     * Rotate logs
     */
    public function rotateLogs()
    {
        // Rotate php log
        Rotation::rotate('php.log');
        Rotation::rotate('worker.log');
    }

    /**
     * call workflow action based on given name
     * @throws editor_Workflow_Exception
     */
    protected function doCronWorkflow(string $fn): void
    {
        $wfm = ZfExtended_Factory::get(editor_Workflow_Manager::class);
        $workflows = $wfm->getWorkflows();
        foreach ($workflows as $wfId) {
            $workflow = $wfm->get($wfId);
            $workflow->hookin()->$fn();
        }
    }
}
