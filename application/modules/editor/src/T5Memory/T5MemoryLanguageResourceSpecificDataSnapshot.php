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

namespace MittagQI\Translate5\T5Memory;

use editor_Services_OpenTM2_Service as Service;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Zend_Config;
use Zend_Registry;

class T5MemoryLanguageResourceSpecificDataSnapshot
{
    public function __construct(
        private readonly Zend_Config $config,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly string $filePath,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        $config = Zend_Registry::get('config');
        $languageResourceRepository = LanguageResourceRepository::create();

        return new self(
            $config,
            $languageResourceRepository,
            APPLICATION_DATA . '/logs/t5memory-specificData.log'
        );
    }

    public function takeSnapshot(): void
    {
        $languageResources = $this->languageResourceRepository->getAllByServiceName(Service::NAME);

        foreach ($languageResources as $languageResource) {
            $specificData = $languageResource->__call('getSpecificData', []);

            file_put_contents(
                $this->filePath,
                sprintf(
                    "%s; %s; %d; %s; %s\n",
                    date('Y-m-d H:i:s p'),
                    $this->config->runtimeOptions->LanguageResources->opentm2->tmprefix,
                    $languageResource->getId(),
                    $languageResource->getName(),
                    $specificData,
                ),
                FILE_APPEND
            );
        }
    }
}
