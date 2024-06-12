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

namespace Translate5\MaintenanceCli\FixScript;

use Symfony\Component\Console\Style\SymfonyStyle;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

/**
 * Just a basic template for fix-scripts that grabs the options of the command and provides basic functionalities
 */
abstract class FixScriptAbstract
{
    /**
     * A debug flag that can be set when calling the script via CLI
     */
    protected bool $debug;

    protected SymfonyStyle $io;

    /**
     * A database-Adapter as presumably needed by most fix-scripts
     */
    protected Zend_Db_Adapter_Abstract $db;

    public function __construct(SymfonyStyle $io, bool $doDebug = true)
    {
        $this->debug = $doDebug;
        $this->io = $io;
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    /**
     * Can be used to generate debug-output
     */
    protected function log(string $text): void
    {
        if ($this->debug) {
            $this->io->writeln($text);
        }
    }

    /**
     * Can be used to generate a run-info
     */
    protected function info(string $text): void
    {
        $this->io->info($text);
    }

    /**
     * Can be used to generate a run-error
     */
    protected function error(string $text): void
    {
        $this->io->error($text);
    }

    /**
     * Must be implemented in inheriting scripts to start the fix
     */
    abstract public function fix(): void;
}
