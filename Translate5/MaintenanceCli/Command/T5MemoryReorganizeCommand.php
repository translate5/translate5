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

namespace Translate5\MaintenanceCli\Command;

use editor_Models_LanguageResources_LanguageResource;
use editor_Services_OpenTM2_Connector as Connector;
use MittagQI\Translate5\Service\T5Memory\Enum\ReorganizeTm;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use ZfExtended_Factory;

final class T5MemoryReorganizeCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:reorganize';

    private const ARGUMENT_UUID = 'uuid';

    protected function configure(): void
    {
        $this->setDescription('Reorganizes the memory tables to reduce the size of the memory tables');
        $this->addArgument(
            self::ARGUMENT_UUID,
            InputArgument::REQUIRED,
            'UUID of the memory to reorganize'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $uuid = $input->getArgument(self::ARGUMENT_UUID);

        $connector = $this->getConnector($uuid);

        if (null === $connector) {
            return self::FAILURE;
        }

        $successful = $this->reorganizeTm($connector);

        if (false === $successful) {
            $this->io->error('Reorganization failed');

            return self::FAILURE;
        }

        if (null !== $successful) {
            $this->io->success('Reorganization finished');
        }

        return self::SUCCESS;
    }

    private function reorganizeTm(Connector $connector): ?bool
    {
        if ($connector->isReorganizingAtTheMoment()) {
            $this->io->warning('Memory is being reorganized at the moment');

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you really want to proceed? (Y/N)', false);

            if (!$helper->ask($this->input, $this->output, $question)) {
                return null;
            }
        }

        if ($connector->isReorganized()) {
            $this->io->text(sprintf(
                "Memory is already reorganized with status %s.\n Clearing status and reorganizing again.",
                $connector->getLanguageResource()->getSpecificData(ReorganizeTm::NAME)
            ));

            $connector->getLanguageResource()->removeSpecificData(ReorganizeTm::NAME);
        }

        return $connector->reorganizeTm();
    }

    private function getLanguageResource(string $uuid): editor_Models_LanguageResources_LanguageResource
    {
        $langResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);

        $langResource->loadByUuid($uuid);

        return $langResource;
    }

    private function getConnector(string $languageResourceUuid): ?Connector
    {
        try {
            $languageResource = $this->getLanguageResource($languageResourceUuid);
        } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
            $this->io->error('Language resource with UUID "' . $languageResourceUuid . '" not found.');

            return null;
        }

        $this->io->text(sprintf(
            'Reorganizing memory "%s" (UUID %s)',
            $languageResource->getName(),
            $languageResourceUuid
        ));

        $connector = new Connector();
        $connector->connectTo(
            $languageResource,
            $languageResource->getSourceLang(),
            $languageResource->getTargetLang()
        );

        return $connector;
    }
}
