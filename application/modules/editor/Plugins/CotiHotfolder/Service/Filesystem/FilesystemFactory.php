<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\MountManager;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\ProjectManagerProvider;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\SendMail;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Tools\FilesystemFactoryInterface;
use MittagQI\Translate5\Tools\FlysystemFactory;
use stdClass;
use Zend_Config;
use Zend_Registry;

class FilesystemFactory implements FilesystemFactoryInterface
{
    public const DEFAULT_HOST_LABEL = 'default';

    public const FILESYSTEM_CONFIG_NAME = 'runtimeOptions.plugins.CotiHotfolder.filesystemConfig';

    public function __construct(
        private readonly T5Logger $logger,
        private readonly CustomerRepository $customerRepository,
        private readonly ProjectManagerProvider $projectManagerProvider,
        private readonly SendMail $mailer,
        private readonly Zend_Config $config,
    ) {
    }

    public static function create(): self
    {
        return new self(
            T5Logger::create(),
            new CustomerRepository(),
            ProjectManagerProvider::create(),
            SendMail::create(),
            Zend_Registry::get('config'),
        );
    }

    public function getFilesystem(string $key): ?FilesystemService
    {
        $config = $this->getFilesystemConfig($key);

        if (empty($config)) {
            return null;
        }

        if (! self::isValidFilesystemConfig($config)) {
            $this->logger->invalidFilesystemConfig($config);

            $message = 'Invalid filesystem configuration provided';

            $customer = null;

            if (self::DEFAULT_HOST_LABEL !== $key) {
                $customer = $this->customerRepository->get((int) base64_decode($key));
                $message .= ' for customer ' . $customer->getName();
            }

            $this->mailer->sendErrorsToPm(
                $this->projectManagerProvider->getFallbackPm($key),
                $customer ? $customer->getNumber() : 'system',
                '',
                [$message]
            );

            return null;
        }

        if (property_exists($config, 'port')) {
            $config->port = (int) $config->port;
        }

        if (property_exists($config, 'timeout')) {
            $config->timeout = (int) $config->timeout;
        }

        if (property_exists($config, 'maxTries')) {
            $config->maxTries = (int) $config->maxTries;
        }

        $flysystem = FlysystemFactory::create($config->type, $config);

        try {
            $flysystem->directoryExists(DIRECTORY_SEPARATOR);
        } catch (FilesystemException|FilesystemOperationFailed) {
            $this->logger->filesystemIsNotReachable($config);

            $message = 'Filesystem is not reachable';

            $customer = null;

            if (self::DEFAULT_HOST_LABEL !== $key) {
                $customer = $this->customerRepository->get((int) base64_decode($key));
                $message = ' for customer ' . $customer;
            }

            $this->mailer->sendErrorsToPm(
                $this->projectManagerProvider->getFallbackPm($key),
                $customer ? $customer->getNumber() : 'system',
                '',
                [$message]
            );

            return null;
        }

        return new FilesystemService(
            new MountManager([
                'local' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, new stdClass()),
                $key => $flysystem,
            ]),
            $this->logger
        );
    }

    private function getFilesystemConfig(string $key): ?object
    {
        if (self::DEFAULT_HOST_LABEL === $key) {
            $filesystemConfig = $this->config->runtimeOptions->plugins?->CotiHotfolder?->filesystemConfig;
            $filesystemConfig = $filesystemConfig ? $filesystemConfig->toArray() : null;

            if (empty($filesystemConfig)) {
                return null;
            }

            return (object) $filesystemConfig;
        }

        $customerId = base64_decode($key);
        $customerConfig = $this->customerRepository->getConfigValue(
            (int) $customerId,
            self::FILESYSTEM_CONFIG_NAME
        );

        return empty($customerConfig) ? null : json_decode($customerConfig);
    }

    /**
     * @param object{type: string, host: string}|object{type: string, location: string}|null $config
     */
    public static function isValidFilesystemConfig(?object $config): bool
    {
        if (empty($config)) {
            return true;
        }

        if (! property_exists($config, 'type')) {
            return false;
        }

        if (FlysystemFactory::TYPE_SFTP === $config->type) {
            return property_exists($config, 'host') && ! empty($config->host);
        }

        return property_exists($config, 'location') && ! empty($config->location);
    }
}
