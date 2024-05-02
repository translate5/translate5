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

class CachePurgeCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'cache:purge';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Cleans the application cache.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Cleans the application cache by deleting all cache files.');
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

        $this->writeTitle('Purging application cache');
        /* @var $cache \Zend_Cache_Core */
        $cache = \Zend_Registry::get('cache');

        if ($cache->clean()) {
            $this->io->success("Application cache purged!");
        } else {
            $this->io->error("Errors on purging application cache!");
        }

        //FIXME ebenfalls memcache löschen!
        // memcache doch in reg legen und alle Nutzungen darauf umbauen, aber erst nach merge von Leons aktueller Glossary Anbindung von 24translate
        $cache = \Zend_Cache::factory('Core', new \ZfExtended_Cache_MySQLMemoryBackend());
        $cache->clean();

        return 0;
    }
}
