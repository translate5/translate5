<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TermImport\Service\Filesystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\MountManager;
use MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;
use MittagQI\Translate5\Tools\FlysystemFactory;
use stdClass;
use Zend_Registry;
use ZfExtended_Factory as Factory;
use editor_Models_Customer_CustomerConfig as CustomerConfig;

class FilesystemFactory
{
    public const DEFAULT_HOST_LABEL = 'default';
    public const FILESYSTEM_CONFIG_NAME = 'runtimeOptions.plugins.TermImport.filesystemConfig';

    private ?object $defaultFilesystemConfig;

    public function __construct(private LoggerService $logger)
    {
        $config = Zend_Registry::get('config');
        $filesystemConfig = $config->runtimeOptions->plugins?->TermImport?->filesystemConfig;
        $filesystemConfig = $filesystemConfig ? (object) $filesystemConfig->toArray() : null;

        if (!$filesystemConfig || ! (array) $filesystemConfig) {
            return;
        }

        if (!self::isValidFilesystemConfig($filesystemConfig)) {
            throw new TermImportException('E1568');
        }

        $this->defaultFilesystemConfig = $filesystemConfig;
    }

    /**
     * @param object{type: string, host: string}|object{type: string, location: string}|null $config
     * @return bool
     */
    public static function isValidFilesystemConfig(?object $config): bool
    {
        if (empty($config)) {
            return true;
        }

        if (!property_exists($config, 'type')) {
            return false;
        }

        if (FlysystemFactory::TYPE_SFTP === $config->type) {
            return property_exists($config, 'host') && !empty($config->host);
        }

        return property_exists($config, 'location') && !empty($config->location);
    }

    private function getFilesystemConfig(string $key): ?object
    {
        if (self::DEFAULT_HOST_LABEL === $key) {
            return $this->defaultFilesystemConfig;
        }

        $customerId = (int) base64_decode($key);

        $value = Factory::get(CustomerConfig::class)
            ->getCurrentValue($customerId, self::FILESYSTEM_CONFIG_NAME);

        return $value === null ? null : json_decode($value);
    }

    public function getFilesystem(string $key): ?FilesystemService
    {
        $config = $this->getFilesystemConfig($key);

        if (empty($config)) {
            return null;
        }

        if (!self::isValidFilesystemConfig($config)) {
            $this->logger->invalidFilesystemConfig($config);

            return null;
        }

        $flysystem = FlysystemFactory::create($config->type, $config);

        try {
            $flysystem->directoryExists('/');
        } catch (FilesystemException|FilesystemOperationFailed) {
            $this->logger->filesystemIsNotReachable($config);

            return null;
        }

        return new FilesystemService(
            new MountManager([
                'local' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, new stdClass()),
                $key => $flysystem,
            ]),
            $this->logger,
            $config->type
        );
    }
}
