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
declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Config;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use editor_Services_OpenTM2_Connector as Connector;
use editor_Services_OpenTM2_Service as Service;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Factory;

class OpenTm2MigrationCommand extends Translate5AbstractCommand
{
    private const ARGUMENT_ENDPOINT = 'endpoint';

    protected static $defaultName = 'otm2:migrate';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Migrates all existing OpenTM2 language resources to t5memory')
            ->setHelp('Tool exports OpenTM2 language resources one by one and imports data to the t5memory provided as endpoint argument')
            ->addArgument(self::ARGUMENT_ENDPOINT, InputArgument::REQUIRED, 't5memory endpoint data to be imported to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $url = $this->getUrl($input);

        $otmResourceId = $this->getOrmResourceId($url);

        $this->updateConfig($url);

        $t5MemoryResourceId = $this->getT5MemoryResourceId($url);

        $languageResourcesData = ZfExtended_Factory::get(LanguageResource::class)->getByResourceId($otmResourceId);

        $connector = new Connector();

        foreach ($languageResourcesData as $languageResourceData) {
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $languageResource->load($languageResourceData['id']);

            $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());

            $filename = APPLICATION_PATH . '/../data/' . $languageResource->getSpecificData('fileName') . '.tmx';

            $type = $connector->getValidExportTypes()['TMX'];

            file_put_contents($filename, $connector->getTm($type));

            $languageResource->setResourceId($t5MemoryResourceId);

            $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());

            $fileinfo = [
                'tmp_name' => $filename,
                'type' => $type,
                'name' => $languageResource->getSpecificData('fileName')
            ];

            $connector->addTm($fileinfo);
        }

        return self::SUCCESS;
    }

    private function getUrl(InputInterface $input): Uri
    {
        $url = new Uri($input->getArgument(self::ARGUMENT_ENDPOINT));

        // TODO validate schema and host?

        return $url;
    }

    private function getOrmResourceId(Uri $url): ?string
    {
        $service = new Service();

        $resourceId = null;
        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                $this->io->warning(sprintf('endpoint %s already exists', $url));

                throw new \Exception('Endpoint already exists');
            }

            if (str_contains($resource->getUrl(), 'otmmemoryservice')) {
                $resourceId = $resource->getId();
            }
        }

        if (null === $resourceId) {
            $this->io->warning('No OpenTM2 resource found');
            throw new \Exception('No OpenTM2 resource found');
        }

        return $resourceId;
    }

    private function updateConfig(Uri $url)
    {
        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $value = json_decode($config->getValue(), true);
        $value[] = (string)$url;
        $config->setValue(json_encode($value));
        $config->save();

        $dbConfig = ZfExtended_Factory::get(\ZfExtended_Resource_DbConfig::class);
        $dbConfig->setBootstrap(Zend_Registry::get('bootstrap'));
        $dbConfig->init();
    }

    private function getT5MemoryResourceId(Uri $url): string
    {
        $service = new Service();

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                return $resource->getId();
            }
        }

        $this->io->info('Something went wrong: no t5memory resource id found');
        throw new \Exception('Something went wrong: no t5memory resource id found');
    }
}
