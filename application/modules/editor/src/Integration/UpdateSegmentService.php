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

namespace MittagQI\Translate5\Integration;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use MittagQI\Translate5\Integration\Contract\UpdateSegmentInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\UpdateSegmentService as T5MemoryService;
use Zend_Config;

class UpdateSegmentService
{
    /**
     * @var array<string, UpdateSegmentInterface>
     */
    private array $instances = [];

    /**
     * @param class-string<UpdateSegmentInterface>[] $updaters
     */
    public function __construct(
        private array $updaters,
    ) {
    }

    public static function create(): self
    {
        return new self([
            T5MemoryService::class,
            DummyFileTm\UpdateSegmentService::class,
        ]);
    }


    public function addService(string $updater): void
    {
        $this->updaters[] = $updater;
    }

    /**
     * @param class-string<UpdateSegmentInterface> $updater
     */
    private function getService(string $updater): UpdateSegmentInterface
    {
        if (! isset($this->instances[$updater])) {
            $this->instances[$updater] = $updater::create();
        }

        return $this->instances[$updater];
    }

    public function update(
        LanguageResource $languageResource,
        Segment $segment,
        Zend_Config $config,
        ?UpdateOptions $updateOptions = null,
    ): void {
        foreach ($this->updaters as $updater) {
            if ($this->getService($updater)->supports($languageResource)) {
                $this->getService($updater)->update($languageResource, $segment, $config, $updateOptions);

                return;
            }
        }
    }

    public function updateWithDTO(
        LanguageResource $languageResource,
        Segment $segment,
        UpdateSegmentDTO $dto,
        Zend_Config $config,
        UpdateOptions $updateOptions,
    ): void {
        foreach ($this->updaters as $updater) {
            if ($this->getService($updater)->supports($languageResource)) {
                $this->getService($updater)->updateWithDTO($languageResource, $segment, $dto, $config, $updateOptions);

                return;
            }
        }
    }
}