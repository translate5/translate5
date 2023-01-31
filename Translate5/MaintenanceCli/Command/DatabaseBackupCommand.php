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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseBackupCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'database:backup';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Create database backup files')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Saves a database backup - by default under data/db-backup/');

    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws \Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $backup_dir = APPLICATION_DATA.'/backup';

        $this->writeTitle('database backup');

        if (!is_dir($backup_dir)) {
           @mkdir($backup_dir);
        }
        if (!is_writable($backup_dir)) {
            throw new \RuntimeException('Backup directory can not be created or is not writable: '.$backup_dir);
        }

        $config = \Zend_Registry::get('config');
        $params = $config->resources->db->params->toArray();

        //FIXME HERE
        // target dir/filename and gzip as param
        // logrotate and log/php_error.log

        try {
            $dump = new \Ifsnop\Mysqldump\Mysqldump($this->makeDsn($params), $params['username'], $params['password']);
            $dump->setInfoHook(function($object, $info) {
                if ($object === 'table') {
                    $this->io->writeln(' '.str_pad($info['name'], 50).' ('.$info['rowCount'].' rows)');
                }
            });
            $this->io->writeln('Backup table: ');
            $dump->start($backup_dir.'/dump.sql');
        } catch (\Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
        }

        return self::SUCCESS;
    }

    protected function makeDsn(array $params): string
    {
        //reuse Zend DSN creation method, we have just to make it public.
        $dsn = new class($params) extends \Zend_Db_Adapter_Pdo_Mysql {
            public function _dsn()
            {
                return parent::_dsn(); //make it public
            }
        };
        return $dsn->_dsn();
    }
}
