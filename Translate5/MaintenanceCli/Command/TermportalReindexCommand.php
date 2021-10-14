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
use Symfony\Component\Console\Question\Question;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;


class TermportalReindexCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'termportal:reindex';

    protected $timezone;

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Re-index the fulltext index of the term database.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Re-index the fulltext index of the term database.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->initInputOutput($input, $output);
        $this->io->title('Translate5 termportal: re-index term DB table.');

        $db = \Zend_Db_Table::getDefaultAdapter();
        $result = $db->query('SELECT @@innodb_optimize_fulltext_only fullonly;');
        $res = $result->fetchObject();
        $result2 = $db->query('SELECT count(*) cnt FROM terms_term;');
        $res2 = $result2->fetchObject();
        $msg = 'Terms table with '.$res2->cnt.' terms successfully re-indexed.';

        if($res->fullonly !== '1') {
            try {
                $db->query('set GLOBAL innodb_optimize_fulltext_only=ON;');
            }
            catch(\Zend_Db_Statement_Exception $e) {
                if(strpos($e->getMessage(), 'Access denied; you need (at least one of) the SUPER') === false) {
                    throw $e;
                }
                $this->io->warning([
                    'The DB user configured in translate5 is only allowed to perform a slow table optimization instead of a fast rebuild of the index.',
                    'Translate5 can either call that slow re-index by choosing yes, or if choosing no the alternative is to call the fast re-index manually as DB root user in the DB directly.',
                    'Therefore open your mysql prompt as root and execute:',
                    '    mysql > SET GLOBAL innodb_optimize_fulltext_only=ON;',
                    '    mysql > OPTIMIZE TABLE terms_term;'
                ]);
                if(!$this->io->confirm('Shall the slow re-index be called?')) {
                    return 0;
                }
                $msg = 'Terms table with '.$res2->cnt.' terms successfully re-created and re-indexed.';
            }
        }
        $db->query('OPTIMIZE TABLE terms_term;');
        $this->io->success($msg);
        return 0;
    }
}
