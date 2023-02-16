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

use Exception;
use Ifsnop\Mysqldump\Mysqldump;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zend_Db_Adapter_Pdo_Mysql;
use Zend_Exception;
use Zend_Registry;

class DatabaseBackupCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'database:backup';
    private bool $useGzip;

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Create database backup files')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Saves a database backup - by default under data/db-backup/');

        $this->addArgument(
            'target',
            InputArgument::OPTIONAL,
            'If exists and is directory, is created there as new file. If does not exist, assume as file name.'
        );

        $this->addOption(
            name: 'gzip',
            shortcut: 'z',
            mode: InputOption::VALUE_NONE,
            description: 'Sort by row count instead of size.'
        );


    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->useGzip = (bool)$input->getOption('gzip');
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('database backup');

        $config = Zend_Registry::get('config');
        $params = $config->resources->db->params->toArray();

        try {
            $dump = new Mysqldump(
                $this->makeDsn($params),
                $params['username'],
                $params['password'],
                [
                    'compress' => $this->useGzip ? Mysqldump::GZIPSTREAM : Mysqldump::NONE
                ]
            );

            $dump->setInfoHook(function ($object, $info) {
                if ($object === 'table') {
                    $this->io->writeln(' ' . str_pad($info['name'], 50) . ' (' . $info['rowCount'] . ' rows)');
                }
            });
            $this->io->writeln(' backing up tables: ');
            $target = $this->modifyNameSuffix($this->getDestination());
            $dump->start($target);
            $this->io->success('Backup file create: ' . $target);
        } catch (Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
        }

        return self::SUCCESS;
    }

    protected function makeDsn(array $params): string
    {
        //reuse Zend DSN creation method, we have just to make it public.
        $dsn = new class($params) extends Zend_Db_Adapter_Pdo_Mysql {
            public function _dsn()
            {
                return parent::_dsn(); //make it public
            }
        };
        return $dsn->_dsn();
    }

    private function modifyNameSuffix(string $filename): string
    {
        if ($this->useGzip && !str_ends_with($filename, '.gz')) {
            $filename .= '.gzip';
        }
        return $filename;
    }

    private function getDestination(): string
    {
        $file = '/' . date('Y-m-d-H-i-s') . '.sql';
        $backupDir = APPLICATION_DATA . '/backup';

        if ($givenTarget = $this->input->getArgument('target')) {
            if (is_dir($givenTarget)) {
                return $givenTarget . $file;
            }
            return $givenTarget;
        }
        if (!is_dir($backupDir)) {
            @mkdir($backupDir);
        }
        if (!is_writable($backupDir)) {
            throw new RuntimeException('Backup directory can not be created or is not writable: ' . $backupDir);
        }
        return $backupDir . $file;
    }
}
