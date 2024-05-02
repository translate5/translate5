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

use MittagQI\Translate5\Service\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Exception;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Plugin_Exception;
use ZfExtended_Plugin_Manager;

class ServiceAutodiscoveryCommand extends Translate5AbstractCommand
{
    protected const ARGUMENT_HOST = 'host';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'service:autodiscovery';

    protected ZfExtended_Plugin_Manager $pluginmanager;

    /**
     * @var array
     * structure: name => [ url (string or array for pooled services), config (optional) ]
     */
    protected array $services = [
        'php' => [
            'url' => 'http://php.:80', // used to configure the worker-trigger access
        ],
        /*
        'proxy' => [
            'url' => 'http://proxy.:80/'
        ],
        */
        't5memory' => [
            'url' => 'http://t5memory.:4040/t5memory',
        ],
        'frontendmessagebus' => [
            'url' => 'http://frontendmessagebus.:9057',
        ],
        'okapi' => [
            'url' => 'http://okapi.:8080', //path part with version is added automatically on locate call
        ],
        'languagetool' => [
            'url' => [
                'default' => ['http://languagetool.:8010/v2'],
                'gui' => ['http://languagetool.:8010/v2'],
                'import' => ['http://languagetool.:8010/v2'],
            ], // pooled service, needs at least 3 entries
        ],
        'termtagger' => [
            'url' => 'http://termtagger:9001',
            'config' => [
                'autodetect' => 20,
            ], // pooled service with autodetect
        ],
        'pdfconverter' => [
            'url' => 'http://pdfconverter.:8086',
        ],
        'visualconverter' => [
            'url' => 'http://visualconverter.:80',
        ],
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
            visualconverter: the internal translate5 container, one instance
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
            'all-available',
            'l',
            InputOption::VALUE_NONE,
            'Discover all available non-configured plugin services'
        );

        $this->addOption(
            'service',
            's',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Specify the service to configure'
        );

        $this->addOption(
            'unterminated-domains',
            'u',
            InputOption::VALUE_NONE,
            'Do not use terminated domains, so instead of "http://service.:1234" use "http://service:1234"'
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

        $this->writeTitle('Translate5 service auto-discovery');

        $services = [];
        $host = null;
        $doSave = (! $this->input->getOption('auto-set')) ? false : true;
        $allAvailable = (! $this->input->getOption('all-available')) ? false : true;
        $useUnterminatedDomains = (! $this->input->getOption('unterminated-domains')) ? false : true;

        // evaluate services to update
        $optionServices = $this->input->getOption('service');
        if (! empty($optionServices)) {
            foreach ($optionServices as $service) {
                $service = strtolower($service);
                if (! array_key_exists($service, $this->services)) {
                    $this->io->error('The service "' . $service . '" is unknown with this command.');
                    $this->io->writeln('Valid services are: ' . join(', ', array_keys($this->services)));

                    return self::FAILURE;
                }
                $services[$service] = $this->services[$service];
            }
            // a single service can be set to a custom host
            $host = empty($this->input->getArgument(self::ARGUMENT_HOST)) ? null : $this->input->getArgument(self::ARGUMENT_HOST);
        } else {
            $services = $this->services;

            if (! empty($this->input->getArgument(self::ARGUMENT_HOST))) {
                $this->io->warning('The host will be ignored when configuring all services');
            }
        }

        $this->setServices($services, $doSave, $allAvailable, $useUnterminatedDomains, $host);

        return self::SUCCESS;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Plugin_Exception
     */
    protected function setServices(array $services, bool $doSave, bool $allAvailableServices = false, bool $useUnterminatedDomains = false, string $host = null)
    {
        if ($allAvailableServices) {
            $allServices = Services::getAllAvailableServices(Zend_Registry::get('config'));
        } else {
            // get configured services
            $this->pluginmanager = Zend_Registry::get('PluginManager');
            $this->pluginmanager->bootstrap(); // load all configured plugins
            $allServices = Services::getAllServices(Zend_Registry::get('config'));
        }

        foreach ($services as $serviceName => $service) {
            if (array_key_exists($serviceName, $allServices)) {
                $configuredService = $allServices[$serviceName];
                if (is_array($service['url'])) {
                    $serviceUrl = $service['url'];
                    if (! array_key_exists('default', $serviceUrl) || ! array_key_exists('gui', $serviceUrl) || ! array_key_exists('import', $serviceUrl)) {
                        throw new Zend_Exception('Service "' . $serviceName . '" pooled URLs are not properly defined');
                    }
                    $serviceUrl['default'] = $this->createServiceUrl($serviceUrl['default'], $host, $useUnterminatedDomains, true);
                    $serviceUrl['gui'] = $this->createServiceUrl($serviceUrl['gui'], $host, $useUnterminatedDomains, true);
                    $serviceUrl['import'] = $this->createServiceUrl($serviceUrl['import'], $host, $useUnterminatedDomains, true);
                } else {
                    $serviceUrl = $this->createServiceUrl($service['url'], $host, $useUnterminatedDomains);
                }
                $serviceConfig = array_key_exists('config', $service) ? $service['config'] : [];

                if ($configuredService->canBeLocated()) {
                    if ($configuredService->locate($this->io, $serviceUrl, $doSave, $serviceConfig)) {
                        if ($configuredService->isPluginService()) {
                            $this->setPluginActive($configuredService->getPluginName(), true, $doSave);
                        } else {
                            $msg = ($doSave) ? 'Have configured service' : 'Would configure service';
                            $this->io->info($msg . ' "' . $serviceName . '"');
                        }
                    } else {
                        if ($configuredService->isPluginService()) {
                            $this->setPluginActive($configuredService->getPluginName(), false, $doSave);
                        } else {
                            $msg = ($doSave) ? 'Have NOT configured service' : 'Would NOT configure service';
                            $this->io->note($msg . ' "' . $serviceName . '"');
                        }
                    }
                } else {
                    $this->io->warning('Service "' . $serviceName . '" is a service that can not be located programmatically.');
                }
            } else {
                $this->io->note('Service "' . $serviceName . '" was not found in the instance\'s configured services probably because the holding plugin is not active.');
            }
        }
    }

    /**
     * Replaces the host if a custom host is given or unterminates the url-host if wanted
     * @param string|array $url
     * @return string|array
     */
    protected function createServiceUrl(mixed $url, string $host = null, bool $unterminateDomains = false, bool $forceArray = false): mixed
    {
        if (is_array($url)) {
            $newUrls = [];
            foreach ($url as $newUrl) {
                $newUrls[] = $this->createServiceUrl($newUrl, $host, $unterminateDomains);
            }

            return $newUrls;
        }
        if (! empty($host) || $unterminateDomains) {
            $host = ! empty($host) ? $host : parse_url($url, PHP_URL_HOST);
            if ($unterminateDomains) {
                $host = trim($host, '.');
            }
            $url =
                parse_url($url, PHP_URL_SCHEME)
                . '://' . $host . ':'
                . parse_url($url, PHP_URL_PORT)
                . (parse_url($url, PHP_URL_PATH) ?? '');
        }

        return ($forceArray && ! is_array($url)) ? [$url] : $url;
    }

    /**
     * En-/Disables a plugin (if auto-set is set)
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function setPluginActive(string $plugin, bool $active = true, bool $doSave = false)
    {
        if ($doSave) {
            $this->pluginmanager->setActive($plugin, $active);
            $this->io->success('Plug-In ' . $plugin . ' ' . ($active ? 'activated.' : 'disabled!'));
        } else {
            $this->io->note('Would ' . ($active ? 'activate' : 'disable') . ' Plug-In ' . $plugin);
        }
    }
}
