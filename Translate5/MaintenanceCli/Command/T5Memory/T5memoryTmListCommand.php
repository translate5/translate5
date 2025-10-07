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

use editor_Services_Manager as ServiceManager;
use editor_Services_OpenTM2_Service as Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\FilteringByNameTrait;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\T5MemoryLocalTmsTrait;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use Zend_Http_Client;
use ZfExtended_Factory;

class T5memoryTmListCommand extends Translate5AbstractCommand
{
    use T5MemoryLocalTmsTrait;
    use FilteringByNameTrait;

    protected static $defaultName = 't5memory:list';

    private const OPTION_TM_NAME = 'tmName';

    protected function configure(): void
    {
        $this->setDescription('Lists all translation memories in t5memory with statuses');
        $this->addOption(
            self::OPTION_TM_NAME,
            'f',
            InputArgument::OPTIONAL,
            'Filter tms by name (case insensitive, partial match)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->io->title('List of all translation memories in t5memory with statuses');

        $table = $this->io->createTable();
        $table->setHeaders(['Tm name', 'Tm UUID', 'Status', 'Server URL']);

        $nameFilter = null;

        if ($this->isFilteringByName()) {
            $nameFilter = $this->input->getOption(self::OPTION_TM_NAME);
            $this->io->note('NAME FILTER: ' . $nameFilter);
        }

        foreach ($this->getLocalTms($nameFilter) as $item) {
            $table->addRow([$item['name'], $item['uuid'], $item['status'], $item['url']]);
        }

        $table->render();

        // TODO add remote tms that do not exist locally after tm list query is fixed on t5memory side

        return self::SUCCESS;
    }

    private function getRemoteTmsList(): array
    {
        $manager = ZfExtended_Factory::get(ServiceManager::class);

        $result = [];

        foreach ($manager->getAllResourcesOfType(Service::NAME) as $resource) {
            $url = rtrim($resource->getUrl(), '/') . '/';

            $http = new Zend_Http_Client($url);
            $tms = $http->request('GET')->getBody();

            foreach ($tms as $tm) {
                $result[$url . ' - ' . $tm['name']] = [
                    'name' => $tm['name'],
                    'url' => $url,
                ];
            }
        }

        return $result;
    }

    protected function getInput(): InputInterface
    {
        return $this->input;
    }
}
