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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\PersistenceService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class SetMemoriesFromServerToLanguageResourceCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:set-memories-from-server-to-language-resource';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('t5memory:set-memories-from-server-to-language-resource')
            ->setDescription('Looks for memories on the t5memory side and sets them to language resources.')
            ->addArgument(
                'lrId',
                InputOption::VALUE_REQUIRED,
                'Language Resource ID to set memories for',
            )
            ->addOption(
                'memories',
                'm',
                InputOption::VALUE_OPTIONAL,
                'JSON with memories to set, e.g. [{"id":0,"filename":"ID1-test","readonly":true},{"id":1,"filename":"ID1-test_next-1","readonly":false}]',
            )
            ->addOption(
                'prefix',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Prefix to filter memories on t5memory server side',
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $languageResourceRepository = LanguageResourceRepository::create();

        $languageResource = $languageResourceRepository->get(
            (int) $input->getArgument('lrId')
        );

        $this->io->info(
            sprintf(
                "Current memories list:\n%s",
                json_encode($languageResource->getSpecificData('memories'))
            )
        );

        if (! empty($input->getOption('memories'))) {
            $memories = json_decode($input->getOption('memories'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->io->error('Invalid JSON provided for memories option.');

                return self::FAILURE;
            }

            $question = new ConfirmationQuestion(
                'Do you want to set provided list as memories for language resource?',
                false
            );

            $proceed = $this->io->askQuestion($question);

            if (! $proceed) {
                $this->io->info('Cancelled.');

                return self::SUCCESS;
            }

            $languageResource->addSpecificData('memories', $memories);
            $languageResourceRepository->save($languageResource);

            return self::SUCCESS;
        }

        $api = T5MemoryApi::create();
        $config = \Zend_Registry::get('config');
        $persistenceService = PersistenceService::create();
        $lrBaseUrl = $languageResource->getResource()->getUrl();

        if ($input->getOption('prefix')) {
            $tmPrefix = $input->getOption('prefix');
        } else {
            $tmPrefix = $persistenceService->addTmPrefix('ID' . $languageResource->getId() . '-');
        }

        $serverMemoryList = [];

        foreach ($config->runtimeOptions->LanguageResources->opentm2->server as $baseUrl) {
            if ($baseUrl !== $lrBaseUrl) {
                continue;
            }

            if (! $api->ping($baseUrl)) {
                $this->io->error('T5Memory server is not reachable: ' . $baseUrl);

                continue;
            }

            $memoryListResponse = $api->getMemories($baseUrl);

            foreach ($memoryListResponse->memories as $memory) {
                if (strpos($memory->name, $tmPrefix) !== 0) {
                    continue;
                }

                $serverMemoryList[] = $memory->name;
            }
        }

        $table = $this->io->createTable();
        $table->setHeaders(['TM Name', 'Server URL']);
        foreach ($serverMemoryList as $tmName) {
            $table->addRow([$tmName, $lrBaseUrl]);
        }
        $table->render();

        $this->io->writeln('');

        $question = new ConfirmationQuestion(
            'Do you want to set this list as memories for language resource?',
            false
        );

        $proceed = $input->getOption('yes') || $this->io->askQuestion($question);

        if (! $proceed) {
            $this->io->info('Cancelled.');

            return self::SUCCESS;
        }

        $languageResource->addSpecificData('memories', []);

        foreach ($serverMemoryList as $tmName) {
            $persistenceService->addMemoryToLanguageResource($languageResource, $tmName);
        }

        $this->io->info(
            sprintf(
                "New memories list:\n%s",
                json_encode($languageResource->getSpecificData('memories'))
            )
        );

        return self::SUCCESS;
    }
}
