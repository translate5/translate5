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

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Translate5\MaintenanceCli\FixScript\FixScriptAbstract;
use Zend_Exception;
use Zend_Http_Client_Exception;
use ZfExtended_Factory;

class PatchScriptCommand extends Translate5AbstractCommand
{
    public const SCRIPTNAME = 'scriptname';

    public const DEBUG = 'debug';

    protected static $defaultName = 'patch:script';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Fixes data by script filename expected to be added to "Translate5/fixscripts"')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Fixes data by calling a script by its filename.'
                . ' The script is expected to be present in Translate5/fixscripts'
                . ' and it is expected to contains a class matching the filename'
                . ' extending Translate5\MaintenanceCli\FixScript\FixScriptAbstract.'
            );

        $this->addArgument(
            self::SCRIPTNAME,
            InputArgument::OPTIONAL,
            'The filename of the script to execute. If not given, one can select of the existing scripts'
        );

        $this->addOption(
            self::DEBUG,
            'd',
            InputOption::VALUE_NONE,
            'If set, the script will run in debug-mode (must be implemented in the script)'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $scriptName = $input->getArgument(self::SCRIPTNAME);
        $doDebug = $input->getOption(self::DEBUG);
        $scriptsDir = APPLICATION_ROOT . '/Translate5/MaintenanceCli/fixscripts';

        if (! empty($scriptName) && ! str_ends_with($scriptName, '.php')) {
            $scriptName .= '.php';
        }

        // if not given / not existing, ask for an existing
        if (empty($scriptName) || ! file_exists($scriptsDir . '/' . $scriptName)) {
            $scriptNames = $this->getAllScriptNames($scriptsDir);
            $question = empty($scriptName) ? 'Please choose a Script' : 'Script "' . $scriptName . '" doesn\'t exist, choose one of the following';
            $askSuites = new ChoiceQuestion($question, $scriptNames, null);
            $scriptName = $this->io->askQuestion($askSuites);
        }

        $scriptPath = $scriptsDir . '/' . $scriptName;

        if (! file_exists($scriptPath)) {
            $this->io->error('Script does not exist: ' . $scriptPath);

            return self::FAILURE;
        }

        $pretitle = $doDebug ? 'Debug' : 'Apply';
        $this->writeTitle($pretitle . ' fix by calling script "' . $scriptName . '"');
        $this->io->newLine(1);

        $this->executeScript($scriptPath, $doDebug);

        $this->io->newLine(2);
        $this->io->success('Script "' . $scriptName . '" executed.');

        return self::SUCCESS;
    }

    /**
     * The list of existing fix-scripts
     * @return string[]
     */
    private function getAllScriptNames(string $scriptsDir): array
    {
        $files = [];
        foreach (glob($scriptsDir . '/*.php') as $path) {
            $files[] = basename($path);
        }

        return $files;
    }

    private function executeScript(string $scriptPath, bool $debug): void
    {
        $className = pathinfo($scriptPath, PATHINFO_FILENAME);

        require_once $scriptPath;

        $scriptInstance = ZfExtended_Factory::get($className, [$this->io, $debug]);
        /** @var FixScriptAbstract $scriptInstance */

        $scriptInstance->fix();
    }
}
