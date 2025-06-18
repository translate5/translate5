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

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager as ServicesManager;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\TaskTm\Db\TaskTmTaskAssociation;
use MittagQI\Translate5\T5Memory\ExportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class T5MemoryExportCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:export';

    private const ARGUMENT_DIRECTORY = 'directory';

    private const STRATEGY_BY_CLIENT = 'by client';

    private const STRATEGY_BY_IDS = 'by comma-separated list of ids';

    protected function configure(): void
    {
        $this->setDescription('Exports specified memories to the specified directory');
        $this->addArgument(
            self::ARGUMENT_DIRECTORY,
            InputArgument::REQUIRED,
            'Directory to export memories to'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $directory = $input->getArgument(self::ARGUMENT_DIRECTORY);

        if (! is_dir($directory)) {
            $this->io->error('Directory ' . $directory . ' does not exist');

            return self::FAILURE;
        }

        if (! is_writable($directory)) {
            $this->io->error('Directory ' . $directory . ' is not writable');

            return self::FAILURE;
        }

        $strategy = $this->askStrategy();
        $languageResources = $this->getLanguageResources($strategy);
        $exportService = ExportService::create();

        foreach ($languageResources as $languageResource) {
            $this->io->info('Exporting ' . $languageResource->getName());

            $memories = count($languageResource->getSpecificData('memories', parseAsArray: true));
            $file = $exportService->export(
                $languageResource,
                TmFileExtension::fromMimeType('application/xml', $memories > 1)
            );

            if (null === $file || ! file_exists($file)) {
                $this->io->error('Export failed: Nothing was exported');

                continue;
            }

            $this->io->info('Exported to ' . $file);

            $filename = pathinfo($file, PATHINFO_FILENAME) . '.' . pathinfo($file, PATHINFO_EXTENSION);

            $this->io->info('Moving to ' . $directory . '/' . $filename);

            rename($file, $directory . '/' . $filename);
        }

        return self::SUCCESS;
    }

    private function askStrategy()
    {
        $question = new ChoiceQuestion(
            'Please chose how do you want to specify language resources to export',
            [self::STRATEGY_BY_CLIENT, self::STRATEGY_BY_IDS],
            self::STRATEGY_BY_IDS
        );

        return $this->io->askQuestion($question);
    }

    /**
     * @return iterable<LanguageResource>
     */
    private function getLanguageResources(string $strategy): iterable
    {
        match ($strategy) {
            self::STRATEGY_BY_CLIENT => yield from $this->getLanguageResourcesByClient(),
            default => yield from $this->getLanguageResourcesByIds(),
        };
    }

    private function getLanguageResourcesByClient(): iterable
    {
        $customerId = $this->io->ask('Please provide client id');

        $languageResource = \ZfExtended_Factory::get(LanguageResource::class);
        $db = $languageResource->db;

        $select = $db->select()
            ->from(
                [
                    'tm' => $db->info($db::NAME),
                ],
                ['tm.*']
            )
            ->setIntegrityCheck(false)
            ->joinLeft(
                [
                    'ca' => \ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME),
                ],
                'tm.id = ca.languageResourceId',
                ''
            )
            ->joinLeft(
                [
                    'ttm' => TaskTmTaskAssociation::TABLE,
                ],
                'tm.id = ttm.languageResourceId',
                'IF(ISNULL(ttm.id), 0, 1) AS isTaskTm'
            )
            ->where('tm.serviceType = ?', ServicesManager::SERVICE_OPENTM2)
            ->where('ca.customerId = ?', $customerId)
            ->where('ttm.id IS NULL')
            ->group('tm.id');

        $data = $db->fetchAll($select);

        foreach ($data as $row) {
            $languageResource->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $db,
                        'data' => $row->toArray(),
                        'stored' => true,
                        'readOnly' => false,
                    ]
                ),
                true
            );

            yield clone $languageResource;
        }
    }

    private function getLanguageResourcesByIds(): iterable
    {
        $ids = $this->io->ask('Please provide comma-separated list of language resource ids');
        $ids = explode(',', trim($ids));
        $ids = array_map('intval', $ids);

        $languageResource = \ZfExtended_Factory::get(LanguageResource::class);
        foreach ($ids as $id) {
            try {
                $languageResource->load($id);
            } catch (\ZfExtended_Models_Entity_NotFoundException) {
                $this->io->error('Language resource with id ' . $id . ' not found');

                continue;
            }

            yield clone $languageResource;
        }
    }
}
