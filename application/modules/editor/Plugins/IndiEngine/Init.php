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

use MittagQI\Translate5\Cronjob\CronEventTrigger;
use MittagQI\Translate5\Plugins\IndiEngine\EventWriter as EventWriter;

/**
 * Initial Class of Plugin "IndiEngine"
 */
class editor_Plugins_IndiEngine_Init extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Send logs to external Indi Engine logger in batch manner';

    protected static bool $enabledByDefault = true;

    protected static bool $activateForTests = true;

    /**
     * The configs that needed to be set/copied for tests
     *
     * @var array[]
     */
    protected static array $testConfigs = [

    ];

    public EventWriter $writer;

    /**
     * Spoof built-in logger writer and attach event handler for cron
     *
     * @throws Zend_Exception
     * @throws ZfExtended_Logger_Exception
     */
    public function init(): void
    {
        // Get logger
        /** @var ZfExtended_Logger $logger */
        $logger = Zend_Registry::get('logger');

        try {
            // Setup writer
            $this->writer = EventWriter::create([
                'type' => EventWriter::class,
                'filter' => ['level <= info'],
            ]);
            /** @phpstan-ignore-next-line  */
        } catch (TypeError $e) {
            $logger->exception($e, [
                'message' => 'TypeError in indi engine setup. Missing Config?',
            ]);

            return;
        }

        // Spoof current db writer with $this->writer
        $logger->addWriter('db', $this->writer);

        // Attach event handler for cron
        $this->eventManager->attach(CronEventTrigger::class, CronEventTrigger::PERIODICAL, [$this, 'handleErrorlog']);
    }

    /**
     * Is called to write events to either stdout or to external IndiEngine HTTP API endpoint
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function handleErrorlog(): void
    {
        // If we're inside bitbucket pipeline run
        if ($_ENV['BITBUCKET_BUILD_NUMBER'] ?? 0) {
            // Just print base64-encoded data, as if will be recognized by Indi Engine
            echo '<Zf_errorlog>' . $this->writer->getBase64() . '</Zf_errorlog>';

            // Else if events have to be sent to external Indi Engine logger instance
        } else {
            // Do that
            $this->writer->batchWrite();
        }
    }
}
