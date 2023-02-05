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

use editor_Models_Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zend_Exception;
use Zend_Registry;


class DevelopmentLocalServicesCommand extends ServiceAutodiscoveryCommand
{

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:localservices';

    /**
     * @var array
     * structure: name => port, for multiinstance services [termtagger, languagetool], the lowest defines the sequence
     */


    private string $revertSql = '';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Local Development only: Searches and sets the dockerized services matching the "docker-compose-localdev.yml" docker-compose-file')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Local Development only: Searches and sets the dockerized services matching the "docker-compose-localdev.yml" docker-compose-file');

        $this->addOption(
            'auto-set',
            'a',
            InputOption::VALUE_NONE,
            'Discover and update the configuration'
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

        $this->writeTitle('Local Development: Service auto-discovery');

        $doSave = (!$this->input->getOption('auto-set')) ? false : true;

        $this->setServices($this->services, $doSave);

        return self::SUCCESS;
    }

    protected array $services = [
        'php' => [
            'url' => 'http://php:80',
            'config' => ['remove' => true] // will remove the worker-config as the server url works for local dev
        ],
        't5memory' => [
            'url' => 'http://localhost:4740/t5memory',
        ],
        'frontendmessagebus' => [
            'url' => 'http://localhost:4757',
            'config' => ['socketServer' => 'ws://localhost:4756/translate5'] // special host/port for local-dev
        ],
        'okapi' => [
            'url' => 'http://localhost:4780/okapi-longhorn/'
        ],
        'languagetool' => [
            'url' => [
                'default' => ['http://localhost:4710/v2', 'http://localhost:4711/v2'],
                'gui' => ['http://localhost:4712/v2'],
                'import' => ['http://localhost:4710/v2', 'http://localhost:4711/v2', 'http://localhost:4712/v2']
            ]
        ],
        'termtagger' => [
            'url' => [
                'default' => ['http://localhost:4701'],
                'gui' => ['http://localhost:4702'],
                'import' => ['http://localhost:4701', 'http://localhost:4702']
            ]
        ],
        'pdfconverter' => [
            'url' => 'http://localhost:4786'
        ],
        'visualbrowser' => [
            'url' => 'ws://localhost:3000' // due to biderectional access, must work in "host" network mode so port cannot be virtualized
        ]
    ];
}
