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

use editor_Models_LanguageResources_LanguageResource;
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\FilteringByNameTrait;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\T5MemoryLocalTmsTrait;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use ZfExtended_Factory;

class T5MemoryDeleteTmCommand extends Translate5AbstractCommand
{
    use T5MemoryLocalTmsTrait;
    use FilteringByNameTrait;

    protected static $defaultName = 't5memory:delete';

    private const OPTION_TM_NAME = 'tmName';

    private const ARGUMENT_UUID = 'uuid';

    protected function configure(): void
    {
        $this->setDescription('Reorganizes particular TM');
        $this->addArgument(
            self::ARGUMENT_UUID,
            InputArgument::OPTIONAL,
            'UUID of the memory to delete, if not given, you can select from a list'
        );
        $this->addOption(
            self::OPTION_TM_NAME,
            'f',
            InputOption::VALUE_REQUIRED,
            'If no UUID was given this will filter the list of all TMs if provided'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $uuid = $this->getTmUuid($input);

        if (null === $uuid) {
            return self::FAILURE;
        }

        try {
            $languageResource = $this->getLanguageResource($uuid);
        } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
            $this->io->error('Language resource not found.');

            return self::FAILURE;
        }

        $successful = $this->deleteLanguageResource($languageResource);

        if (false === $successful) {
            $this->io->error('Failed to delete TM');

            return self::FAILURE;
        }

        $this->io->success('TM successfully deleted');

        return self::SUCCESS;
    }

    private function deleteLanguageResource($languageResource): bool
    {
        try {
            DeleteLanguageResourceOperation::create()->delete(
                $languageResource,
                forced: true,
                deleteInResource: true
            );

            return true;
        } catch (\ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->io->error('Fail to delete language resource: ' . $e->getMessage());

            return false;
        }
    }

    private function getLanguageResource(string $uuid): editor_Models_LanguageResources_LanguageResource
    {
        $langResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);

        $langResource->loadByUuid($uuid);

        return $langResource;
    }

    protected function getInput(): InputInterface
    {
        return $this->input;
    }
}
