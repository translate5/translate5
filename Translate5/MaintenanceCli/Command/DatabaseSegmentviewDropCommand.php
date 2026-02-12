<?php
declare(strict_types=1);
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

use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Exception;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Installer_DbUpdater as DbUpdater;
use ZfExtended_Models_Installer_Maintenance;

class DatabaseSegmentviewDropCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'database:segmentview:drop';

    protected function configure(): void
    {
        $this
            ->setDescription('Drop materialized segment views.')
            ->setHelp('Drops all materialized segment views. Maintenance mode must be enabled!');
    }

    /**
     * @throws Zend_Db_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('database management');

        $maintenanceMode = new ZfExtended_Models_Installer_Maintenance();
        if (! $maintenanceMode->isActive()) {
            $this->io->error('Maintenance mode is disabled but must be enabled to drop segment materialized views.');

            return self::FAILURE;
        }

        if (! $this->io->confirm('Drop all segment materialized views now? Ensure no workers are running!', false)) {
            $this->io->warning('Action cancelled.');

            return self::SUCCESS;
        }

        $dbUpdater = ZfExtended_Factory::get(DbUpdater::class, [true]);
        $dbUpdater->dropSegmentMaterializedViews();
        \Zend_Registry::get('cache')->clean();

        $this->io->success('Dropped all segment materialized views.');

        return self::SUCCESS;
    }
}
