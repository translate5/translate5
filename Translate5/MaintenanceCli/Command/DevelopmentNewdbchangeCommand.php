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

class DevelopmentNewdbchangeCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:newdbchange';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Creates a new DB alter file, gets the filename from the current branch.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Creates a new DB alter file, gets the filename from the current branch.');

        $this->addArgument('path',
            InputArgument::OPTIONAL,
            'Path which should be used. Plugin or ZfExtended or default module, editor database is the default if empty.'
        );
        
        $this->addOption(
            'php',
            'p',
            InputOption::VALUE_NONE,
            'Creates a PHP instead of a SQL file.');
        
        $this->addOption(
            'name',
            'N',
            InputOption::VALUE_REQUIRED,
            'Force a name instead of getting it from the branch.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        
        $this->writeTitle('Create a new database alter file');
        
        $dbDirectory = $this->getDirectory();
        
        if($name = $input->getOption('name')) {
            $name = $this->getFileName($dbDirectory, $name);
        }
        else {
            $gitout = [];
            exec('cd '.$dbDirectory.'; git branch --show-current', $gitout);
            if(empty($gitout)) {
                $this->io->error('git could not find local branch!');
                return 1;
            }
            
            $name = $this->getFileName($dbDirectory, reset($gitout));
        }
        
        if($input->getOption('php')) {
            $this->makePhp($dbDirectory, $name);
        }
        else {
            $this->makeSql($dbDirectory, $name);
            //add deinstall for Plugins too.
            if(strpos($dbDirectory, 'Plugins/') !== false) {
                $this->makeSql($dbDirectory, 'deinstall_'.$name);
            }
        }
        
        return 0;
    }
    
    /**
     * returns the directory to be used.
     * @throws \Exception
     * @return string|string|string[]|NULL
     */
    protected function getDirectory() {
        $search = $this->input->getArgument('path');
        if(empty($search)) {
            return APPLICATION_PATH.'/modules/editor/database';
        }
        if(!is_dir($search)) {
            throw new \Exception('Given path is no directory! Path: '.$search);
        }
        $dir = basename($search);
        if($dir != 'database' && $dir != 'docs') {
            $this->io->warning(['Given path does not end with database or docs (for default module). Please check that and move the created files.','Path: '.$search]);
        }
        return $search;
    }
    
    /**
     * returns the new filename for the alter file (without suffix!)
     * @param string $dbDirectory
     * @param string $branch
     * @return string
     */
    protected function getFileName(string $dbDirectory, string $branch): string {
        $name = explode('/', $branch);
        if(count($name) > 1) {
            //remove feature / fix prefix
            array_shift($name);
        }
        $name = join('-', $name);
        
        $dirs = scandir($dbDirectory);
        $numbers = [];
        foreach($dirs as $dir) {
            if(preg_match('/^([0-9]+)-/', $dir, $match)) {
                $numbers[] = $match[1];
            }
        }
        if(empty($numbers)) {
            $next = '001';
        }
        else {
            $next = str_pad(max($numbers) + 1, 3, '0', STR_PAD_LEFT);
        }
        
        return $next.'-'.$name;
    }
    
    protected function makeSql($dir, $name) {
        $file = $dir.'/'.$name.'.sql';
        $this->io->success('Created file '.$file);
        file_put_contents($file, "-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

Reminder:
CREATE TABLE Statements
  without DB name
  without charset and without collation, unless you know exactly what you do!

Template Config:
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.xxx.yyy', '1', 'app', 'system', 'VALUE', 'DEFAULT', '', 'string', 'DESC.', 2, 'place me in the GUI', 'place me in the GUI');
// Choose the guiGroup wisely!
// CONFIG_LEVEL_SYSTEM      = 1;
// CONFIG_LEVEL_INSTANCE    = 2;
// CONFIG_LEVEL_CUSTOMER    = 4;
// CONFIG_LEVEL_TASKIMPORT  = 8;
// CONFIG_LEVEL_TASK        = 16;
// CONFIG_LEVEL_USER        = 32;

Template ACL:
INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
VALUES (null, 'editor', 'noRights', 'editor_fakelang', 'all');

");
    }

    protected function makePhp($dir, $name) {
        $file = $dir.'/'.$name.'.php';
        $this->io->success('Created file '.$file);
        file_put_contents($file, '<?php '.
$this->getTranslate5LicenceText().'

/**
  README: '.$name.'
  DESCRIPTION WHAT THIS SCRIPT DOES!
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = \''.$name.'.php\';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
// $res = $db->query("EXAMPLE");

');
    }
}
