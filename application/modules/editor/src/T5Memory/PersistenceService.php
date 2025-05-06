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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Zend_Config;

class PersistenceService
{
    public function __construct(
        private readonly Zend_Config $config
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('config'),
        );
    }

    public function getWritableMemory(LanguageResource $languageResource): string
    {
        foreach ($languageResource->getSpecificData('memories', parseAsArray: true) ?? [] as $memory) {
            if (! $memory['readonly']) {
                return $memory['filename'];
            }
        }

        throw new \editor_Services_Connector_Exception('E1564', [
            'name' => $languageResource->getName(),
        ]);
    }

    public function getLastWritableMemory(LanguageResource $languageResource): string
    {
        $memories = [];

        foreach ($languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            if (! $memory['readonly']) {
                $memories[] = $memory['filename'];
            }
        }

        if (empty($memories)) {
            throw new \editor_Services_Connector_Exception('E1564', [
                'name' => $languageResource->getName(),
            ]);
        }

        return array_pop($memories);
    }

    public function getNextWritableMemory(LanguageResource $languageResource, string $memoryName): ?string
    {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        $pattern = '/_next-(\d+)/';

        preg_match($pattern, $memoryName, $matches);
        $memoryNumber = (int) ($matches[1] ?? 0);

        $matches = [];
        foreach ($memories as $memory) {
            if ($memory['readonly']) {
                continue;
            }

            if (! preg_match($pattern, $memory['filename'], $matches)) {
                continue;
            }

            if ((int) $matches[1] > $memoryNumber) {
                return $memory['filename'];
            }
        }

        return null;
    }

    /**
     * adds the internal TM prefix to the given TM name
     */
    public function addTmPrefix(string $tmName): string
    {
        //CRUCIAL: the prefix (if any) must be added on usage, and may not be stored in the specificName
        // that is relevant for security on a multi hosting environment
        $prefix = $this->config->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (! empty($prefix) && ! str_starts_with($tmName, $prefix . '-')) {
            $tmName = $prefix . '-' . $tmName;
        }

        return $tmName;
    }

    public function addMemoryToLanguageResource(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): void {
        $prefix = $this->config->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (! empty($prefix)) {
            //remove the prefix from being stored into the TM
            $tmName = str_replace('^' . $prefix . '-', '', '^' . $tmName);
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        $lastMemoryId = 0;

        if (! empty($memories)) {
            $lastMemoryId = end($memories)['id'];
        }

        $memories[] = [
            'id' => $lastMemoryId + 1,
            'filename' => $tmName,
            'readonly' => false,
        ];

        $languageResource->addSpecificData('memories', $memories);

        if (! $isInternalFuzzy) {
            //saving it here makes the TM available even when the TMX import was crashed
            $languageResource->save();
        }
    }

    public function setMemoryReadonly(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): void {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        foreach ($memories as $key => $memory) {
            if ($memory['filename'] === $tmName) {
                $memories[$key]['readonly'] = true;
            }
        }

        $languageResource->addSpecificData('memories', $memories);

        if (! $isInternalFuzzy) {
            $languageResource->save();
        }
    }
}
