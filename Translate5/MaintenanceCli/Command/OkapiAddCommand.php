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

use MittagQI\Translate5\Plugins\Okapi\ConfigMaintenance;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class OkapiAddCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:add';

    protected function configure()
    {
        $this
            ->setDescription('Add a new okapi server')
            ->setHelp('Add a new Okapi Server to the configuration, is NOT set as default automatically.
            See okapi:update');

        $this->addArgument(
            'url',
            InputArgument::REQUIRED,
            'The URL to the new Okapi instance.'
        );

        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'The name of the new Okapi instance, defaults to the path part which contains the version.
            If a same named entry exists, the URL is updated.'
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

        $this->writeTitle('Add Okapi server');

        $url = $this->input->getArgument('url');
        $name = $this->input->getArgument('name');

        if (empty($name)) {
            $name = basename($url);
        }

        $config = new ConfigMaintenance();
        $oldValue = $config->addServer($url, $name);
        if (empty($oldValue)) {
            $this->io->success('Added ' . $name . ' with ' . $url);
        } else {
            $this->io->warning('Reset ' . $name . ' from ' . $oldValue . ' to ' . $url);
        }

        return self::SUCCESS;
    }
}
