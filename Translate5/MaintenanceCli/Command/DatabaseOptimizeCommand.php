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

namespace Translate5\MaintenanceCli\Command;

use MittagQI\Translate5\Tools\DatabaseOptimizer;
use MittagQI\Translate5\Tools\DatabaseOptimizer\ReportDto;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db;
use Zend_Db_Exception;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Models_Installer_Maintenance;

class DatabaseOptimizeCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'database:optimize';

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Optimize database tables with high data  fluctuation.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Optimizes either the tables where due high data fluctuation more disk space is used as '
            . ' required. Or perform optimize on all tables - instances must be in maintenance therefore.');

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Optimize ALL tables - instance must be in maintenance therefore.'
        );
    }

    /**
     * Execute the command
     * @throws Zend_Exception
     * @throws Zend_Db_Exception
     * @throws Zend_Db_Statement_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('database management');

        $dbConfig = Zend_Registry::get('config')->resources->db;

        $optimizer = new DatabaseOptimizer(Zend_Db::factory($dbConfig));
        $maintenanceMode = new ZfExtended_Models_Installer_Maintenance();

        $writer = function (ReportDto $reportDto) {
            if ($reportDto->statusOk) {
                $this->io->writeln($reportDto->table . ' - OK: <info>' . $reportDto->text . '</info>');
            } else {
                $this->io->writeln($reportDto->table . ': <error>' . $reportDto->text . '</error>');
            }
        };

        if ($input->getOption('all')) {
            if (! $maintenanceMode->isActive()) {
                $this->io->error('Maintenance mode is disabled but must be enabled for optimizing all tables.');

                return self::FAILURE;
            }
            $this->io->section('Optimize all tables');
            $optimizer->optimizeAll($writer);
        } else {
            $this->io->section('Optimize the tables which mostly needs it');
            $optimizer->optimizeDaily($writer);
        }

        return self::SUCCESS;
    }
}
