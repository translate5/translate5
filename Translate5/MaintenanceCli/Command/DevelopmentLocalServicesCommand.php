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
    protected array $services = [
        'php' => 80, // will remove the worker-config as the server url works for local dev
        'frontendmessagebus' => 4757,
        'okapi' => 4780,
        'languagetool' => 4710,
        't5memory' => 4740,
        'termtagger' => 4701,
        'pdfconverter' => 4786,
        'visualbrowser' => 3000 // due to biderectional access, must work in "host" network mode so port cannot be virtualized
    ];

    private string $revertSql = '';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Local Development only: Searches and sets the dockerized services matching the "docker-compose-localdev.yml" docker-compose-file')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Local Development only: Searches and sets the dockerized services matching the "docker-compose-localdev.yml" docker-compose-file');

        $this->addArgument(
            self::ARGUMENT_HOST,
            InputArgument::OPTIONAL,
            'Custom host for the service. Applicable only when discovering a specific service.'
        );

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

        $this->pluginmanager = Zend_Registry::get('PluginManager');

        $this->writeTitle('Local Development: Service auto-discovery');

        $this->foreachService($this->services);

        if ($this->input->getOption('auto-set')) {
            $this->io->writeln('');
            $this->io->note('You can revert the changes with the following SQL:');
            $this->io->write($this->revertSql);
        }
        $this->io->writeln('');

        return self::SUCCESS;
    }

    /**
     * @param int $port
     * @throws Zend_Exception
     */
    protected function serviceFrontendmessagebus(int $port)
    {
        $host = $this->getHost('frontendmessagebus');
        $config = Zend_Registry::get('config');
        $internalServer = 'http://' . $host . ':' . $port;

        if (!$this->checkServiceDefault('FrontEndMessageBus', $internalServer, $host, $port)) {
            $this->setPluginActive('FrontEndMessageBus', false);
            return;
        }
        if ($config->runtimeOptions->server->protocol === 'https://') {
            $this->io->error('The localdev configuration will not work with SSL/HTTPS');
            $this->setPluginActive('FrontEndMessageBus', false);
            return;
        }
        $this->updateConfig('runtimeOptions.plugins.FrontEndMessageBus.messageBusURI', $internalServer);
        $this->updateConfig(
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost',
            $host
        );
        $this->updateConfig(
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port',
            ($port - 1)
        );
        $this->updateConfig(
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route',
            '/translate5' // just /translate5 on direct access to the socket server
        );
        $this->updateConfig(
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema',
            'ws'
        );
        $this->setPluginActive('FrontEndMessageBus', true);
    }

    protected function servicePhp(int $port): void
    {
        // for localhost environments the worker URL stays empty
        $this->updateConfig(
            'runtimeOptions.worker.server',
            ''
        );
    }

    /**
     * Finds the languagetool-instances
     * expects three languagetools defined, languagetool_1 and languagetool_2, languagetool_3
     * @param int $port
     * @return array[]
     */
    protected function findLanguagetools(int $port): array
    {
        $found = [
            'default' => [],
            'gui' => [],
            'import' => [],
        ];
        for($i = 0; $i < 3; $i++){
            $host = $this->getHost('languagetool_'.($i + 1));
            $url = 'http://' . $host . ':' . ($port + $i) . '/v2';
            if ($this->checkServiceDefault('Languagetool '.($i + 1), $url, $host, ($port + $i))){
                if($i == 0){
                    $found['gui'][] = $url;
                } else {
                    $found['default'][] = $url;
                }
                $found['import'][] = $url;
            }
        }
        return $found;
    }

    /**
     * expects two termtaggers defined, termtagger_1 and termtagger_2
     * @param int $port
     * @return array[]
     */
    protected function findTermtaggers(int $port): array
    {
        //
        $found = [
            'default' => [],
            'gui' => [],
            'import' => [],
        ];
        $host = $this->getHost('termtagger_1');
        if ($this->isDnsSet($host, $port)) {
            $found['default'][] = 'http://' . $host . ':' . $port;
            $found['import'][] = 'http://' . $host . ':' . $port;
        }
        $host = $this->getHost('termtagger_2');
        $port++;
        if ($this->isDnsSet($host, $port)) {
            $found['gui'][] = 'http://' . $host . ':' . $port;
            $found['import'][] = 'http://' . $host . ':' . $port;
        }
        return $found;
    }

    /**
     * @param string $label
     * @param string $url
     * @param string $host
     * @param int $port
     * @return bool
     */
    protected function checkServiceDefault(string $label, string $url, string $host, int $port): bool
    {
        $result = true;
        $url = $host . ':' . $port;
        if (!$this->isDnsSet($host, $port)) {
            $url = 'NONE (expected: ' . $url . ')';
            $result = false;
        }
        $this->io->info('Found ' . $label . ': ' . $url);
        return $result;
    }

    /**
     * @param string $host
     * @param int $port
     * @return bool
     */
    protected function isDnsSet(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port);
        if (is_resource($connection)){
            fclose($connection);
            return true;
        }
        return false;
    }

    /**
     * No host-by servicename with local development
     * @param string $default
     * @return string
     */
    protected function getHost(string $default): string
    {
        return $this->input->getArgument(self::ARGUMENT_HOST) ?? 'localhost';
    }

    /**
     * Updates the config model instance and prints info about it
     * @param editor_Models_Config $config
     * @param string $newValue
     * @return void
     * @throws Zend_Exception
     */
    protected function updateConfigInstance(editor_Models_Config $config, string $newValue): void
    {
        if ($this->input->getOption('auto-set')) {
            $this->revertSql .= "UPDATE `Zf_configuration` SET `value` = '".$config->getValue()."' WHERE `name` = '".$config->getName()."';\n";
        }
        parent::updateConfigInstance($config, $newValue);
    }

    /**
     * Needs to be rerouted to ensure t5memory is set to the desired value only
     * @param editor_Models_Config $config
     * @param string $newValue
     * @throws Zend_Exception
     * @throws \JsonException
     */
    protected function addToListConfigInstance(editor_Models_Config $config, string $newValue): void
    {
        $this->updateListConfigInstance($config, [ $newValue ]);
    }
}
