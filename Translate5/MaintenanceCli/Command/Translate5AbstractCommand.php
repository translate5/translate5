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
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Style\SymfonyStyle;


abstract class Translate5AbstractCommand extends Command
{
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
    
    /**
     * @var Application
     */
    protected $translate5;
    
    /**
     * initializes io class variables
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initInputOutput(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
    }
    
    /**
     * Initializes the translate5 application bridge (setup the translate5 Zend Application so that Models and the DB can be used)
     */
    protected function initTranslate5() {
        $this->translate5 = new Application();
        $this->translate5->init();
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
    protected function writeTitle(string $title) {
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
