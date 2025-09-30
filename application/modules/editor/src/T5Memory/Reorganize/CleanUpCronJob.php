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

namespace MittagQI\Translate5\T5Memory\Reorganize;

use MittagQI\Translate5\Maintenance\CleanUpFolders;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\PersistenceService;

class CleanUpCronJob
{
    private const string THRESHOLD_DAYS = '-10 days';

    public function __construct(
        private readonly CleanUpFolders $cleanUpFolders,
        private readonly T5MemoryApi $api,
        private readonly \Zend_Config $config,
        private readonly PersistenceService $persistenceService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new CleanUpFolders(),
            T5MemoryApi::create(),
            \Zend_Registry::get('config'),
            PersistenceService::create(),
        );
    }

    public function cleanUp(): void
    {
        $this->removeOldTmxBackUps();
        $this->removeOldTmBuckUps();
    }

    public function removeOldTmxBackUps(): void
    {
        $this->cleanUpFolders->deleteOldDateFolders(
            ManualReorganizeService::REORGANIZE_DIR,
            new \DateTime(self::THRESHOLD_DAYS)
        );
    }

    public function removeOldTmBuckUps(): void
    {
        $tmPrefix = $this->persistenceService->addTmPrefix('');
        $threshold = new \DateTime(self::THRESHOLD_DAYS);

        foreach ($this->config->runtimeOptions->LanguageResources->opentm2->server as $baseUrl) {
            if (! $this->api->ping($baseUrl)) {
                continue;
            }

            $memoryListResponse = $this->api->getMemories($baseUrl);

            foreach ($memoryListResponse->memories as $memory) {
                if ('' !== $tmPrefix && ! str_starts_with($memory->name, $tmPrefix)) {
                    continue;
                }

                if (preg_match('/reorganise\.(\d{4}-\d{2}-\d{2})\.(before|after)/', $memory->name, $matches)) {
                    $memoryDate = new \DateTime($matches[1]);

                    if ($memoryDate < $threshold) {
                        $this->api->deleteTm($baseUrl, $memory->name);
                    }
                }
            }
        }
    }
}