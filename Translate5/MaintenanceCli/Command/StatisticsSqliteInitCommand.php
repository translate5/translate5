<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\SQLite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Registry;

class StatisticsSqliteInitCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'statistics:sqlite:init';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Create missing SQLite statistics DB/tables.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Create SQLite DB/tables for statistics aggregation (if missing).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        if (! extension_loaded('sqlite3')) {
            $this->io->error('Missing "sqlite3" PHP extension');

            return self::FAILURE;
        }

        $config = Zend_Registry::get('config');
        $dbFileName = trim($config->resources?->db?->statistics?->sqliteDbname);

        if (! is_file($dbFileName)) {
            $dbFileDir = dirname($dbFileName);
            is_dir($dbFileDir) || mkdir($dbFileDir, recursive: true);
            touch($dbFileName);
            $this->io->writeln('Created writeable SQLite DB File: ' . $dbFileName);
        }
        chmod($dbFileName, 0666);

        $db = SQLite::create();

        $tableSql = [
            SegmentHistoryAggregation::TABLE_NAME => 'CREATE TABLE %s (
taskGuid TEXT,
userGuid TEXT,
workflowName TEXT,
workflowStepName TEXT,
segmentId INTEGER,
editable INTEGER,
duration INTEGER,
matchRate INTEGER,
langResType TEXT,
langResId INTEGER,
PRIMARY KEY (taskGuid,segmentId,workflowStepName,userGuid)
)',
            SegmentHistoryAggregation::TABLE_NAME_LEV => 'CREATE TABLE %s (
taskGuid TEXT,
userGuid TEXT,
workflowName TEXT,
workflowStepName TEXT,
segmentId INTEGER,
editable INTEGER,
lastEdit INTEGER,
levenshteinOriginal INTEGER,
levenshteinPrevious INTEGER,
matchRate INTEGER,
langResType TEXT,
langResId INTEGER,
PRIMARY KEY (taskGuid,segmentId,workflowStepName)
)',
        ];

        foreach ($tableSql as $tableName => $sql) {
            if ($db->tableExists($tableName)) {
                $this->io->writeln('SQLite table already exists: ' . $tableName);

                continue;
            }

            $db->query(sprintf($sql, $tableName));

            if ($db->tableExists($tableName)) {
                $this->io->writeln('SQLite table created successfully: ' . $tableName);
            } else {
                $this->io->error('Create SQLite table ' . $tableName . ' FAILED');

                return self::FAILURE;
            }
        }

        $msg = '';
        if ($config->resources?->db?->statistics?->enabled !== '1') {
            $msg .= ' To enable statistics aggregation, add the following line into installation.ini:' . "\n" .
                'resources.db.statistics.enabled = 1' . "\n\n";
        }
        if (strtolower((string) $config->resources?->db?->statistics?->engine) !== 'sqlite') {
            $msg .= ' To use SQLite for statistics aggregation, add the following line into installation.ini:' . "\n" .
                'resources.db.statistics.engine = "SQLite"' . "\n\n";
        }

        $this->io->success($msg . ' To calculate missing levenshtein values in segment history, run the following command:' . "\n" .
            't5 statistics:levenshtein' . "\n\n" .
            ' To aggregates segments history data into Statistics DB, run the following command:' . "\n" .
            't5 statistics:aggregate');

        return self::SUCCESS;
    }
}
