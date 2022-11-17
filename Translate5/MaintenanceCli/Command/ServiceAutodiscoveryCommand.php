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
use JsonException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Plugin_Manager;


class ServiceAutodiscoveryCommand extends Translate5AbstractCommand
{
    private const ARGUMENT_HOST = 'host';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'service:autodiscovery';
    private ZfExtended_Plugin_Manager $pluginmanager;

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Searches for common DNS names of used services and sets them in the configuration,
using the default ports.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Service autodiscovery and configuration by common DNS names:
            languagetool: languagetool spellchecker (scalable on node level by DNS round robin docker-compose --scale)
            t5memory: t5memory TM, scalable only behind a load balancer with consistent hash algorithm
            termtagger: one instance
            termtagger_N: multiple instances, where N is an integer, 1 to 20
            termtagger_TYPE_N: multiple instances by type, where TYPE one of default, gui, import and N as above
            frontendmessagebus: FrontEndMessage Bus, one instance
            okapi: Okapi, currently only one instance
            pdfconverter: the internal translate5 container, one instance
            visualbrowser: the headless browser, one instance
        ');

        $this->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'Discover a specific service only'
        );

        $this->addArgument(
            'host',
            InputArgument::OPTIONAL,
            'Custom host for the service. Applicable only when discovering a specific service.'
        );

        $this->addOption(
            'auto-set',
            's',
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

        $this->writeTitle('Translate5 service auto-discovery');

        $services = [
            'languagetool',
            't5memory',
            'termtagger',
            'frontendmessagebus',
            'okapi',
            'pdfconverter',
            'visualbrowser',
        ];
        $service = $this->input->getArgument('service');

        //discover only the given service
        if ($service && in_array($service, $services)) {
            $services = [$service];
        }

        $this->foreachService($services);

        return self::SUCCESS;
    }

    /**
     * @param array $services
     * @return void
     * @uses  serviceOkapi()
     * @uses  serviceT5memory()
     * @uses  serviceTermtagger()
     * @uses  serviceTermtagger()
     * @uses  serviceLanguagetool()
     * @uses  serviceFrontendmessagebus()
     * @uses  servicePdfconverter()
     * @uses  serviceVisualbrowser()
     */
    private function foreachService(array $services): void
    {
        foreach ($services as $service) {
            call_user_func([$this, 'service' . ucfirst($service)]);
        }
    }

    private function checkServiceDefault(string $name, string $label, string $url): bool
    {
        $result = true;
        if (!$this->isDnsSet($name)) {
            $url = 'NONE';
            $result = false;
        }
        $this->io->writeln('');
        $this->io->writeln('Found ' . $label . ': ' . $url);
        return $result;
    }

    /**
     * @throws JsonException|Zend_Exception
     */
    private function serviceT5memory(): void
    {
        $host = $this->getHost('t5memory');
        $url = 'http://' . $host . ':4040/t5memory';

        if (!$this->checkServiceDefault($host, 'T5Memory', $url)) {
            return;
        }

        $config = new editor_Models_Config();
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $servers = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

        if (!in_array($url, $servers)) {
            $servers[] = $url;
            $servers = json_encode($servers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->updateConfigInstance($config, $servers);
        }

    }

    /**
     * @throws Zend_Exception
     */
    private function serviceFrontendmessagebus()
    {
        $host = $this->getHost('frontendmessagebus');
        $internalServer = 'http://' . $host . ':9057';

        if (!$this->checkServiceDefault($host, 'FrontEndMessageBus', $internalServer)) {
            $this->pluginmanager->setActive('FrontEndMessageBus', false);
            $this->io->success('Plug-In FrontEndMessageBus disabled!');
            return;
        }
        $this->pluginmanager->setActive('FrontEndMessageBus');
        $this->io->success('Plug-In FrontEndMessageBus activated!');
        $config = Zend_Registry::get('config');
        //$config->runtimeOptions.server.name

        //  runtimeOptions.plugins.FrontEndMessageBus.messageBusURI           db       http://127.0.0.1:9057
        $this->updateConfig('runtimeOptions.plugins.FrontEndMessageBus.messageBusURI', $internalServer);
        $this->updateConfig(
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost',
            //FIXME add getenv for especially set different server, like our messagebus.translate5.net
            $config->runtimeOptions->server->name
        );
        if ($config->runtimeOptions->server->protocol === 'https://') {
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port',
                '443'
            );
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route',
                '/wss/translate5'
            );
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema',
                'wss'
            );
        } else {
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port',
                '80' //9056 on direct access to the socket server
            );
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route',
                '/ws/translate5' // just /translate5 on direct access to the socket server
            );
            $this->updateConfig(
                'runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema',
                'ws'
            );
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function serviceOkapi(): void
    {
        $host = $this->getHost('okapi');
        $url = 'http://' . $host . ':8080/okapi-longhorn/';
        //FIXME multiple servers / versions???

        if ($this->checkServiceDefault($host, 'Okapi', $url)) {
            //runtimeOptions.plugins.Okapi.server       {"okapi-longhorn":"http://localhost:8080/okapi-longhorn/"}
            $this->updateConfig(
                'runtimeOptions.plugins.Okapi.server',
                '{"okapi-longhorn":"' . $url . '"}'
            );

            //runtimeOptions.plugins.Okapi.serverUsed   okapi-longhorn
            $this->updateConfig('runtimeOptions.plugins.Okapi.serverUsed', 'okapi-longhorn');
            $this->pluginmanager->setActive('Okapi');
            $this->io->success('Plug-In Okapi activated.');
        } else {
            $this->pluginmanager->setActive('Okapi', false);
            $this->io->success('Plug-In Okapi disabled!');
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function serviceLanguagetool(): void
    {
        $host = $this->getHost('languagetool');
        $url = 'http://' . $host . ':8010/v2';

        if ($this->checkServiceDefault($host, 'Languagetool', $url)) {
            // runtimeOptions.plugins.SpellCheck.languagetool.url.default ["http://localhost:8010/v2"]
            $this->updateConfig('runtimeOptions.plugins.SpellCheck.languagetool.url.default', '["' . $url . '"]');

            // runtimeOptions.plugins.SpellCheck.languagetool.url.gui http://localhost:8010/v2
            $this->updateConfig('runtimeOptions.plugins.SpellCheck.languagetool.url.gui', $url);

            // runtimeOptions.plugins.SpellCheck.languagetool.url.import ["http://localhost:8010/v2"]
            $this->updateConfig('runtimeOptions.plugins.SpellCheck.languagetool.url.import', '["' . $url . '"]');

            $this->updateConfig('runtimeOptions.plugins.SpellCheck.liveCheckOnEditing', '1');
            $this->pluginmanager->setActive('SpellCheck');
            $this->io->success('Plug-In SpellCheck activated.');
        } else {
            $this->pluginmanager->setActive('SpellCheck', false);
            $this->io->success('Plug-In SpellCheck disabled!');
        }
    }

    /**
     * Auto discover termtaggers: either termtagger, or termtagger_N (max 20), or termtagger_TYPE_N (max 20)
     * @return void
     * @throws JsonException
     * @throws Zend_Exception
     */
    private function serviceTermtagger(): void
    {
        $port = '9001'; //default port
        $found = [
            'default' => [],
            'gui' => [],
            'import' => [],
        ];
        $host = $this->getHost('termtagger');

        if ($this->isDnsSet($host)) {
            $found['default'][] = 'http://' . $host . ':' . $port;
        }
        $types = array_keys($found);
        for ($i = 1; $i <= 20; $i++) {
            $hostname = $host . '_' . $i;
            if ($this->isDnsSet($hostname)) {
                $found['default'][] = 'http://' . $hostname . ':' . $port;
            }
            foreach ($types as $type) {
                $hostname = $host . '_' . $type . '_' . $i;
                if ($this->isDnsSet($hostname)) {
                    $found[$type][] = 'http://' . $hostname . ':' . $port;
                }
            }
        }
        $taggers = array_merge(... array_values($found));
        $this->io->writeln('');
        if (empty($taggers)) {
            $this->io->writeln('Found TermTaggers: NONE');
        } else {
            $this->io->writeln('Found TermTaggers: ' . join(', ', $taggers));
        }

        $foundATagger = false;
        foreach ($found as $key => $value) {
            if (empty($found[$key])) {
                continue;
            }
            $value = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $foundATagger = true;
            $this->updateConfig('runtimeOptions.termTagger.url.' . $key, $value);
        }
        $this->pluginmanager->setActive('TermTagger', $foundATagger);
        $this->io->success('Plug-In TermTagger '.($foundATagger ? 'activated.' : 'disabled!'));
    }

    private function servicePdfconverter(): void
    {
        $url = 'http://' . $this->getHost('pdfconverter') . ':8086';

        if (!$this->checkServiceDefault('pdfconverter', 'PDF Converter', $url)) {
            return;
        }

        $this->updateConfig('runtimeOptions.plugins.VisualReview.pdfconverterUrl', $url);
    }

    private function serviceVisualbrowser(): void
    {
        $url = 'ws://' . $this->getHost('headless.chrome') . ':3000';

        if (!$this->checkServiceDefault('headless.chrome', 'Headless Chrome browser', $url)) {
            return;
        }

        $this->updateConfig('runtimeOptions.plugins.VisualReview.dockerizedHeadlessChromeUrl', $url);
    }

    /**
     * Updates a config by name
     * @param string $name
     * @param string $newValue
     * @return void
     * @throws Zend_Exception
     */
    private function updateConfig(string $name, string $newValue): void
    {
        $config = new editor_Models_Config();
        $config->loadByName($name);
        $this->updateConfigInstance($config, $newValue);
    }

    /**
     * Updates the config model instance and prints info about it
     * @param editor_Models_Config $config
     * @param string $newValue
     * @return void
     * @throws Zend_Exception
     */
    private function updateConfigInstance(editor_Models_Config $config, string $newValue): void
    {
        if (! $this->input->getOption('auto-set')) {
            $this->printCurrentConfig($config, '; discovered value is '.$newValue);
            return;
        }
        if ($config->hasIniEntry()) {
            $this->io->warning($config->getName() . ' is set in ini and can not be updated!');
            return;
        }
        if ($config->getValue() === $newValue) {
            $this->printCurrentConfig($config, ' is already set');
            return;
        }
        $config->setValue($newValue);
        $config->save();
        $this->io->success($config->getName() . ' set to ' . $newValue);
    }

    /**
     * @throws Zend_Exception
     */
    private function printCurrentConfig(editor_Models_Config $config, string $suffix = ''): void
    {
        if ($config->hasIniEntry()) {
            $is = ' is in INI: ';
        } else {
            $is = ' is: ';
        }
        $this->io->writeln('  config ' . $config->getName() . $is . $config->getValue() . $suffix);
    }

    private function isDnsSet($serviceName): bool
    {
        $ip = gethostbyname($serviceName);
        return $ip !== $serviceName;
    }

    /**
     * Retrieve particular host from input or return default value if not provided
     *
     * @param string $default
     *
     * @return string
     */
    private function getHost(string $default): string
    {
        return $this->input->getArgument(self::ARGUMENT_HOST) ?? $default;
    }
}
