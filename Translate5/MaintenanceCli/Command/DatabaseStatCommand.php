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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zend_Db;
use Zend_Exception;
use Zend_Registry;

class DatabaseStatCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'database:stat';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Brief statistics about the database')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Brief statistics about the database');

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Show all tables, by default only the 15 biggest ones.'
        );
        $this->addOption(
            'sort-row-count',
            'r',
            InputOption::VALUE_NONE,
            'Sort by row count instead of size.'
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

        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $dbConfig = Zend_Registry::get('config')->resources->db;
        $db = Zend_Db::factory($dbConfig);
        $sql = 'SELECT TABLE_NAME AS `table`, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS `size (MB)`,
                    TABLE_ROWS AS `row count`
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?';

        if ($this->input->getOption('sort-row-count')) {
            $sql .= ' ORDER BY TABLE_ROWS DESC';
        } else {
            $sql .= ' ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC';
        }

        if (!$this->input->getOption('all')) {
            $sql .= ' limit 15';
        }

        $stats = $db->query($sql, [$dbConfig->params->dbname]);
        $this->writeTable($stats->fetchAll());
        return self::SUCCESS;
    }
}
