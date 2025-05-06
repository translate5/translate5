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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_OpenTM2_Service;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\FilteringByNameTrait;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\T5MemoryLocalTmsTrait;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use ZfExtended_Factory;

final class T5MemoryReorganizeCommand extends Translate5AbstractCommand
{
    use T5MemoryLocalTmsTrait;
    use FilteringByNameTrait;

    protected static $defaultName = 't5memory:reorganize|memory:reorganize';

    private const OPTION_TM_NAME = 'tmName';

    private const ARGUMENT_UUID = 'uuid';

    private const OPTION_BATCH_SIZE = 'batchSize';

    private const OPTION_START_FROM_ID = 'startFromId';

    protected function configure(): void
    {
        $this->setDescription('Reorganizes particular TM');
        $this->addArgument(
            self::ARGUMENT_UUID,
            InputArgument::OPTIONAL,
            'UUID of the memory to reorganize, if not given, you can select from a list'
        );
        $this->addOption(
            self::OPTION_TM_NAME,
            'f',
            InputOption::VALUE_REQUIRED,
            'If no UUID was given this will filter the list of all TMs if provided'
        );
        $this->addOption(
            self::OPTION_BATCH_SIZE,
            null,
            InputOption::VALUE_REQUIRED,
            'Number of memories to reorganize at once. Works only if no UUID and tmName was given',
        );
        $this->addOption(
            self::OPTION_START_FROM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'The DB ID of the language resource to start reorganize from. Works only if no UUID and tmName was given',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->assertInputValid($input);

        $languageResources = $this->getLanguageResourcesForReorganization($input);
        $reorganizeService = ReorganizeService::create();

        foreach ($languageResources as $languageResource) {
            $this->io->text(sprintf(
                'Reorganizing all memories in language resource "%s" (ID %s, UUID %s)',
                $languageResource->getName(),
                $languageResource->getId(),
                $languageResource->getLangResUuid()
            ));

            foreach ($languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
                $tmName = $memory['filename'];
                $this->reorganizeTm($reorganizeService, $languageResource, $tmName);
            }
        }

        $this->io->success('Finished');

        return self::SUCCESS;
    }

    private function reorganizeTm(
        ReorganizeService $reorganizeService,
        LanguageResource $languageResource,
        string $tmName
    ): void {
        if ($reorganizeService->isReorganizingAtTheMoment($languageResource, $tmName)) {
            $this->io->warning('Memory "' . $tmName . '" is being reorganized at the moment');

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you really want to proceed? (Y/N)', false);

            if (! $helper->ask($this->input, $this->output, $question)) {
                return;
            }
        }

        $this->io->text('Reorganizing memory "' . $tmName . '"');

        $reorganizeService->reorganizeTm($languageResource, $tmName);
    }

    /**
     * @return iterable<LanguageResource>
     */
    private function getLanguageResourcesForReorganization(InputInterface $input): iterable
    {
        $batchSize = $input->getOption(self::OPTION_BATCH_SIZE);
        $startFromId = $input->getOption(self::OPTION_START_FROM_ID);

        if ($batchSize) {
            return $this->getLanguageResourcesBatch((int) $batchSize, (int) $startFromId);
        }

        $uuid = $this->getTmUuid($input);

        if (null !== $uuid) {
            return $this->getLanguageResourcesByUuid($uuid);
        }

        return [];
    }

    private function getLanguageResourcesBatch(int $batchSize, int $startFromId): iterable
    {
        $db = ZfExtended_Factory::get(LanguageResource::class)->db;
        $s = $db->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], ['lr.langResUuid AS uuid'])
            ->where('lr.id > ?', $startFromId)
            ->where('lr.serviceName = ?', editor_Services_OpenTM2_Service::NAME)
            ->limit($batchSize);

        $data = $db->fetchAll($s);

        foreach ($data as $row) {
            yield $this->getLanguageResource($row['uuid']);
        }
    }

    private function getLanguageResourcesByUuid(string $uuid): iterable
    {
        try {
            return [$this->getLanguageResource($uuid)];
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            $this->io->error('Language resource with UUID "' . $uuid . '" not found.');
        }

        return [];
    }

    private function getLanguageResource(string $uuid): LanguageResource
    {
        $langResource = ZfExtended_Factory::get(LanguageResource::class);

        $langResource->loadByUuid($uuid);

        return $langResource;
    }

    protected function getInput(): InputInterface
    {
        return $this->input;
    }

    private function assertInputValid(InputInterface $input): void
    {
        $uuid = $input->getArgument(self::ARGUMENT_UUID);
        $tmName = $input->getOption(self::OPTION_TM_NAME);
        $batchSize = $input->getOption(self::OPTION_BATCH_SIZE);
        $startFromId = $input->getOption(self::OPTION_START_FROM_ID);

        if (($uuid || $tmName) && ($batchSize || $startFromId)) {
            throw new \InvalidArgumentException(
                'Please either provide `UUID/tmName` or `batchSize/startFromId`'
            );
        }
    }
}
