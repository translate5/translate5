<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use editor_Services_OpenTM2_Service as Service;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\PersistenceService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class T5MemoryCleanUpCommand extends Translate5AbstractCommand
{
    private const OPTION_CLEAN_UP = 'clean-up';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('t5memory:clean-up')
            ->setDescription('Searches for orphaned memories and records without actual memories on t5memory side.' .
                ' Deletes files on t5memory side or removes invalid records.')
            ->addOption(
                self::OPTION_CLEAN_UP,
                'c',
                InputOption::VALUE_OPTIONAL,
                'If true will process clean up, false - dry-run, only lists found problems (default false)',
                false
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $api = ConstantApi::create();
        $config = \Zend_Registry::get('config');

        $serverMemoryList = [];

        foreach ($config->runtimeOptions->LanguageResources->opentm2->server as $baseUrl) {
            if (! $api->ping($baseUrl)) {
                $this->io->error('T5Memory server is not reachable: ' . $baseUrl);

                continue;
            }

            $memoryListResponse = $api->getMemories($baseUrl);

            foreach ($memoryListResponse->memories as $memory) {
                $serverMemoryList[$memory->name] = $baseUrl;
            }
        }

        $languageResourceRepository = LanguageResourceRepository::create();
        $persistenceService = PersistenceService::create();

        $languageResources = $languageResourceRepository->getAllByServiceName(Service::NAME);

        $lrMemoryList = [];

        $memoriesWithoutFiles = [];

        foreach ($languageResources as $languageResource) {
            $memories = $languageResource->getSpecificData('memories', true);

            foreach ($memories as $memory) {
                $tmName = $persistenceService->addTmPrefix($memory['filename']);

                if (! isset($serverMemoryList[$tmName])) {
                    $memoriesWithoutFiles[] = [
                        'languageResource' => $languageResource,
                        'memoryName' => $tmName,
                    ];

                    continue;
                }

                $lrMemoryList[$tmName] = true;
            }
        }

        $orphanedMemories = [];

        foreach ($serverMemoryList as $memoryName => $baseUrl) {
            if (! isset($lrMemoryList[$memoryName])) {
                $orphanedMemories[] = [
                    'memoryName' => $memoryName,
                    'baseUrl' => $baseUrl,
                ];
            }
        }

        if (! $input->hasOption(self::OPTION_CLEAN_UP)) {
            $this->renderReport($memoriesWithoutFiles, $orphanedMemories);

            $this->io->note('No clean up performed. Use --clean-up option to perform clean up.');

            return self::SUCCESS;
        }

        $this->io->section('Performing clean up...');

        foreach ($memoriesWithoutFiles as $item) {
            $languageResource = $item['languageResource'];
            $memoryName = $item['memoryName'];

            $persistenceService->removeMemoryFromLanguageResource($languageResource, $memoryName);
            $this->io->success(
                "Removed memory '$memoryName' from language resource ID: {$languageResource->getId()}"
            );
        }

        foreach ($orphanedMemories as $item) {
            $baseUrl = $item['baseUrl'];
            $memoryName = $item['memoryName'];

            try {
                $api->deleteTm($baseUrl, $memoryName);
                $this->io->success("Deleted orphaned memory '$memoryName' from T5Memory server: $baseUrl");
            } catch (\Exception $e) {
                $this->io->error("Failed to delete orphaned memory '$memoryName' from T5Memory server: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function renderReport(array $memoriesWithoutFiles, array $orphanedMemories): void
    {
        $this->io->title('T5Memory Clean Up Report');

        $this->io->section('Memories without files on T5Memory side');

        if (empty($memoriesWithoutFiles)) {
            $this->io->success('No memories without files found.');
        } else {
            $table = $this->io->createTable();
            $table->setHeaders(['Language Resource ID', 'Language Resource Name', 'Memory Name']);
            foreach ($memoriesWithoutFiles as $item) {
                $table->addRow([
                    $item['languageResource']->getId(),
                    $item['languageResource']->getName(),
                    $item['memoryName'],
                ]);
            }
            $table->render();
        }

        $this->io->section('Orphaned memories on T5Memory side');

        if (empty($orphanedMemories)) {
            $this->io->success('No orphaned memories found.');
        } else {
            $table = $this->io->createTable();
            $table->setHeaders(['Memory Name', 'Base URL']);
            foreach ($orphanedMemories as $item) {
                $table->addRow([
                    $item['memoryName'],
                    $item['baseUrl'],
                ]);
            }
            $table->render();
        }
    }
}
