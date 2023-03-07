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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Style\SymfonyStyle;


abstract class Translate5AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected InputInterface $input;
    
    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;
    
    /**
     * @var SymfonyStyle
     */
    protected SymfonyStyle $io;
    
    /**
     * @var Application
     */
    protected Application $translate5;

    /**
     * if true output should be machine-readable!
     * @var bool
     */
    protected bool $isPorcelain = false;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->addOption(
            name: 'porcelain',
            mode: InputOption::VALUE_NONE,
            description: 'Return the output in a machine readable way - if implemented in the command.'
        );
    }

    /**
     * initializes io class variables
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initInputOutput(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;
        if ($input->getOption('porcelain')) {
            $this->isPorcelain = true;
            $output->setDecorated(false);
        }
        $this->io = new SymfonyStyle($input, $output);
    }
    
    /**
     * Initializes the translate5 application bridge (setup the translate5 Zend Application so that Models and the DB can be used)
     * @throws \Zend_Exception
     */
    protected function initTranslate5(string $applicationEnvironment = 'application') {
        $this->translate5 = new Application();
        $this->translate5->init($applicationEnvironment);
    }

    /**
     * Initializes the translate5 application bridge
     * If the installation is setup for API tests, it will ask for the environment to bootstrap, otherwise the application environment will be initialized without notice
     * This API expects  input & output to be inited
     * @throws \Zend_Exception
     */
    protected function initTranslate5AppOrTest() {
        // the app is uninitalized, so we cannot use APPLICATION_PATH
        $installationIniFile = getcwd().'/application/config/installation.ini';
        $iniVars = file_exists($installationIniFile) ? parse_ini_file($installationIniFile) : false;
        if($iniVars !== false && array_key_exists('testSettings.testsAllowed', $iniVars) && $iniVars['testSettings.testsAllowed'] === '1'){
            $question = new Question('Which database shall be used ? For the test-DB, type "t" or "test", anything else will use the application DB', 'application');
            $answer = strtolower($this->io->askQuestion($question));
            $environment = ($answer === 't' || $answer === 'test') ? 'test' : 'application';
            $this->initTranslate5($environment);
            $config = \Zend_Registry::get('config');
            $this->io->info('Using database "'.$config->resources->db->params->dbname.'"');
        } else {
            $this->initTranslate5();
        }
    }

    protected function getLogo() {
        $logo = <<<EOF
        <fg=bright-yellow;options=reverse>            </>
    <fg=bright-yellow;options=reverse>                    </>
  <fg=bright-yellow;options=reverse>                        </>
 <fg=bright-yellow;options=reverse>   _                      </> _       _       <fg=cyan;options=bold>_____</> 
<fg=bright-yellow;options=reverse>   | |                     |</> |     | |     <fg=cyan;options=bold>| ____|</>
<fg=bright-yellow;options=reverse>   | |_ _ __ __ _ _ __  ___|</> | __ _| |_ ___<fg=cyan;options=bold>| |__  </>
<fg=bright-yellow;options=reverse>   | __| "__/ _` | '_ \/ __|</> |/ _` | __/ _ <fg=cyan;options=bold>\___ \ </>
<fg=bright-yellow;options=reverse>   | |_| | | (_| | | | \__ \ </>| (_| | ||  __<fg=cyan;options=bold>/___) |</>
 <fg=bright-yellow;options=reverse>   \__|_|  \__,_|_| |_|___/</>_|\__,_|\__\___<fg=cyan;options=bold>|____/ </>
  <fg=bright-yellow;options=reverse>                         </>
    <fg=bright-yellow;options=reverse>                    </>
        <fg=bright-yellow;options=reverse>            </>
EOF;

        return $logo;
    }
    
    /**
     * Shows a title and instance information. Should be used in each translate5 command.
     * @param string $title
     */
    protected function writeTitle(string $title): void
    {
        if ($this->isPorcelain) {
            $this->output->write($this->translate5->getHostname().' ('.$this->translate5->getVersion().'): ');
            return;
        }

        $this->io->title($title);
        
        $this->output->writeln([
            '  <info>HostName:</> '.$this->translate5->getHostname(),
            '   <info>AppRoot:</> '.APPLICATION_ROOT,
            '   <info>Version:</> '.$this->translate5->getVersion(),
            '',
        ]);
    }

    protected function writeAssoc(array $data) {
        $keys = array_keys($data);
        $maxlen = max(array_map('strlen', $keys)) + 1;
        foreach($data as $key => $value) {
            $key = str_pad($key, $maxlen, ' ', STR_PAD_LEFT);
            $key = '<info>'.$key.'</info> ';
            $this->output->writeln($key.OutputFormatter::escape((string) $value));
        }
        $this->output->writeln('');
    }

    /**
     * Writes a table and reads out the assoc keys of the child items as headline
     * @param array $data
     */
    protected function writeTable(array $data) {
        if(empty($data)) {
            return;
        }
        $headers = array_map('ucfirst', array_keys(reset($data)));
        $this->io->table($headers, $data);
    }

    
    /***
     * Translate5 licence text to be reused in each command
     * @return string
     */
    protected function getTranslate5LicenceText() {
        return '
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/'
;
    }
}
