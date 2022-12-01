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
    protected const ARGUMENT_HOST = 'host';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'service:autodiscovery';
    /**
     * @var ZfExtended_Plugin_Manager
     */
    protected ZfExtended_Plugin_Manager $pluginmanager;
    /**
     * @var array
     * structure: name => port
     */
    protected array $services = [
        'php' => 80, // used to configure the worker-trigger & visualbrowser access
        'frontendmessagebus' => 9057,
        'okapi' => 8080,
        'languagetool' => 8010,
        'termtagger' => 9001,
        't5memory' => 4040,
        'pdfconverter' => 8086,
        'visualbrowser' => 3000
    ];

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

        $this->addOption(
            'service',
            's',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Specify the service to configure'
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

        // check service option
        $optionServices = $this->input->getOption('service');
        if (!empty($optionServices)) {
            $services = [];
            foreach($optionServices as $service){
                $service = strtolower($service);
                if (!array_key_exists($service, $this->services)) {
                    $this->io->error('The service "' . $service . '" is unknown with this command.');
					$this->io->writeln('Valid services are: '.join(', ', array_keys($this->services)));
                    return self::FAILURE;
                }
                $services[$service] = $this->services[$service];
            }

        } else {
            $services = $this->services;
        }

        $this->foreachService($services);

        return self::SUCCESS;
    }

    /**
     * @param array $services
     * @return void
     * @uses  servicePhp()
     * @uses  serviceProxy()
     * @uses  serviceOkapi()
     * @uses  serviceT5memory()
     * @uses  serviceTermtagger()
     * @uses  serviceTermtagger()
     * @uses  serviceLanguagetool()
     * @uses  serviceFrontendmessagebus()
     * @uses  servicePdfconverter()
     * @uses  serviceVisualbrowser()
     */
    protected function foreachService(array $services): void
    {
        foreach ($services as $service => $port) {
            call_user_func([$this, 'service' . ucfirst($service)], $port);
        }
    }

    protected function servicePhp(int $port): void
    {
        $host = $this->getHost('php');
        $url = 'http://' . $host . ':' . $port;
        if (!$this->checkServiceDefault('php (Translate5)', $url, $host, $port)) {
            return;
        }
        $this->updateConfig(
            'runtimeOptions.worker.server',
            $url
        );
    }

    private function serviceProxy(int $port): void
    {
        $host = $this->getHost('proxy');
        $url = 'http://' . $host . ':80/';

        if (!$this->checkServiceDefault($host, 'Proxy', $url)) {
            return;
        }

        $config = new editor_Models_Config();
        $config->loadByName('runtimeOptions.authentication.ipbased.useLocalProxy');
        $this->updateListConfigInstance($config, $host);
    }

    /**
     * @param int $port
     * @throws JsonException|Zend_Exception
     */
    protected function serviceT5memory(int $port): void
    {
        $host = $this->getHost('t5memory');
        $url = 'http://' . $host . ':' . $port . '/t5memory';

        if (!$this->checkServiceDefault('T5Memory', $url, $host, $port)) {
            return;
        }

        $config = new editor_Models_Config();
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $this->updateListConfigInstance($config, $url);
    }

    /**
     * @param int $port
     * @throws Zend_Exception
     */
    protected function serviceFrontendmessagebus(int $port)
    {
        $host = $this->getHost('frontendmessagebus');
        $internalServer = 'http://' . $host . ':' . $port;

        if (!$this->checkServiceDefault('FrontEndMessageBus', $internalServer, $host, $port)) {
            $this->setPluginActive('FrontEndMessageBus', false);
            return;
        }

        $this->setPluginActive('FrontEndMessageBus');

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
     * @param int $port
     * @throws Zend_Exception
     */
    protected function serviceOkapi(int $port): void
    {
        $host = $this->getHost('okapi');
        $url = 'http://' . $host . ':' . $port . '/okapi-longhorn/';
        //FIXME multiple servers / versions???

        if ($this->checkServiceDefault('Okapi', $url, $host, $port)) {
            //runtimeOptions.plugins.Okapi.server       {"okapi-longhorn":"http://localhost:8080/okapi-longhorn/"}
            $this->updateConfig(
                'runtimeOptions.plugins.Okapi.server',
                '{"okapi-longhorn":"' . $url . '"}'
            );

            //runtimeOptions.plugins.Okapi.serverUsed   okapi-longhorn
            $this->updateConfig('runtimeOptions.plugins.Okapi.serverUsed', 'okapi-longhorn');
            $this->setPluginActive('Okapi');
        } else {
            $this->setPluginActive('Okapi', false);
        }
    }

    /**
     * @param int $port
     * @throws Zend_Exception
     */
    protected function serviceLanguagetool(int $port): void
    {
        $foundInstaces = $this->findLanguagetools($port);
        $pluginActive = $this->configureMultiInstanceService($foundInstaces, 'Languagetool', 'runtimeOptions.plugins.SpellCheck.languagetool', true);
        $this->setPluginActive('TermTagger', $pluginActive);
        if($pluginActive){
            // enable live check when plugin is active
            $this->updateConfig('runtimeOptions.plugins.SpellCheck.liveCheckOnEditing', '1');
        }
    }

    /**
     * Finds the languagetool-instances
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
        // TODO FIXME: can't we have more than one ??
        $host = $this->getHost('languagetool');
        $url = 'http://' . $host . ':' . $port . '/v2';
        if ($this->checkServiceDefault('Languagetool', $url, $host, $port)){
            $found['default'][] = $url;
            $found['gui'][] = $url;
            $found['import'][] = $url;
        }
        return $found;
    }

    /**
     * @param int $port
     * Auto discover termtaggers: either termtagger, or termtagger_N (max 20), or termtagger_TYPE_N (max 20)
     * @return void
     * @throws JsonException
     * @throws Zend_Exception
     */
    protected function serviceTermtagger(int $port): void
    {
        $foundInstaces = $this->findTermtaggers($port);
        $pluginActive = $this->configureMultiInstanceService($foundInstaces, 'TermTagger', 'runtimeOptions.termTagger', false);
        $this->setPluginActive('TermTagger', $pluginActive);
    }

    /**
     * Finds the termtagger-instances
     * @param int $port
     * @return array[]
     */
    protected function findTermtaggers(int $port): array
    {
        $found = [
            'default' => [],
            'gui' => [],
            'import' => [],
        ];
        $host = $this->getHost('termtagger');

        if ($this->isDnsSet($host, $port)) {
            $found['default'][] = 'http://' . $host . ':' . $port;
        }
        $types = array_keys($found);
        for ($i = 1; $i <= 20; $i++) {
            $hostname = $host . '_' . $i;
            if ($this->isDnsSet($hostname, $port)) {
                $found['default'][] = 'http://' . $hostname . ':' . $port;
            }
            foreach ($types as $type) {
                $hostname = $host . '_' . $type . '_' . $i;
                if ($this->isDnsSet($hostname, $port)) {
                    $found[$type][] = 'http://' . $hostname . ':' . $port;
                }
            }
        }
        return $found;
    }

    /**
     * @param int $port
     * @throws Zend_Exception
     */
    protected function servicePdfconverter(int $port): void
    {
        $host = $this->getHost('pdfconverter');
        $url = 'http://' . $host . ':' . $port;

        if (!$this->checkServiceDefault('PDF Converter', $url, $host, $port)) {
            return;
        }

        $this->updateConfig('runtimeOptions.plugins.VisualReview.pdfconverterUrl', $url);
    }

    /**
     * @param int $port
     * @throws Zend_Exception
     */
    protected function serviceVisualbrowser(int $port): void
    {
        $host = $this->getHost('visualbrowser');
        $url = 'ws://' . $host . ':' . $port;

        if (!$this->checkServiceDefault('Headless Chrome browser', $url, $host, $port)) {
            return;
        }

        $this->updateConfig('runtimeOptions.plugins.VisualReview.dockerizedHeadlessChromeUrl', $url);
    }

    /**
     * @param array $foundServices: hashtable with keys "default", "gui" and "import"
     * @param string $serviceName
     * @param string $configBase
     * @param bool $singleGuiInstance: ugly divergency in the way multi-instance-services are set up in the config
     * @return bool
     * @throws JsonException
     * @throws Zend_Exception
     */
    protected function configureMultiInstanceService(array $foundServices, string $serviceName, string $configBase, bool $singleGuiInstance): bool
    {
        $activatePlugin = false;
        // we need all 3 service-url-types to enable the plugin
        if (count($foundServices['default']) < 1 || count($foundServices['gui']) < 1 || count($foundServices['import']) < 1) {
            $this->io->info('Found ' . $serviceName . 's: NONE');
        } else {
            $this->io->info('Found ' . $serviceName . 's: ' . json_encode($foundServices, JSON_UNESCAPED_SLASHES));
            $activatePlugin = true;            
        }
        foreach ($foundServices as $key => $value) {
            if (empty($value)) {
                $newValue = ($key === 'gui' && $singleGuiInstance) ? '' : '[]';
            } else {
                $newValue = ($key === 'gui' && $singleGuiInstance) ? $value[0] : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            }
            $this->updateConfig($configBase . '.url.' . $key, $newValue);
        }
        return $activatePlugin;
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
        $ip = gethostbyname($host);
        return $ip !== $host;
    }

    /**
     * Updates a config by name
     * @param string $name
     * @param string $newValue
     * @return void
     * @throws Zend_Exception
     */
    protected function updateConfig(string $name, string $newValue): void
    {
        $config = new editor_Models_Config();
        $config->loadByName($name);
        $this->updateConfigInstance($config, $newValue);
    }

    /**
     * En-/Disables a plugin (if auto-set is set)
     * @param string $plugin
     * @param bool $active
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function setPluginActive(string $plugin, bool $active = true)
    {
        if ($this->input->getOption('auto-set')) {
            $this->pluginmanager->setActive($plugin, $active);
            $this->io->success('Plug-In ' . $plugin . ' ' . ($active ? 'activated.' : 'disabled!'));
        } else {
            $this->io->note('Would ' . ($active ? 'activate.' : 'disable') . ' Plug-In ' . $plugin);
        }
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
        if (!$this->input->getOption('auto-set')) {
            $this->printCurrentConfig($config, '; discovered value is ' . $newValue);
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
        $this->io->note($config->getName() . ' set to ' . $newValue);
    }

    /**
     * @throws Zend_Exception|JsonException
     */
    private function updateListConfigInstance(editor_Models_Config $config, string $newValue): void
    {
        $servers = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

        if (!in_array($newValue, $servers)) {
            $servers[] = $newValue;
            $servers = json_encode($servers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->updateConfigInstance($config, $servers);
        }
    }

    /**
     * @throws Zend_Exception
     */
    protected function printCurrentConfig(editor_Models_Config $config, string $suffix = ''): void
    {
        if ($config->hasIniEntry()) {
            $is = ' is in INI: ';
        } else {
            $is = ' is: ';
        }
        $this->io->writeln('  config ' . $config->getName() . $is . $config->getValue() . $suffix);
    }

    /**
     * Retrieve particular host from input or return default value if not provided
     *
     * @param string $default
     *
     * @return string
     */
    protected function getHost(string $default): string
    {
        return $this->input->getArgument(self::ARGUMENT_HOST) ?? $default;
    }
}
