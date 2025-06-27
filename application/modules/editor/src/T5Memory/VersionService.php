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

use DateTime;
use DateTimeInterface;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\Api\Contract\HasVersionInterface;
use PharIo\Version\GreaterThanOrEqualToVersionConstraint;
use PharIo\Version\Version;

class VersionService
{
    private bool $isInternalFuzzy = false;

    public function __construct(
        private readonly HasVersionInterface $versionFetchingApi,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ConstantApi::create(),
        );
    }

    public function isLRVersionGreaterThan(string $version, ?LanguageResource $languageResource): bool
    {
        $constraint = new GreaterThanOrEqualToVersionConstraint($version, new Version($version));

        return $constraint->complies(new Version($this->getT5MemoryVersion($languageResource)));
    }

    public function setInternalFuzzy(bool $isFuzzy): void
    {
        $this->isInternalFuzzy = $isFuzzy;
    }

    public function getT5MemoryVersion(?LanguageResource $languageResource): string
    {
        if (! $languageResource) {
            return HasVersionInterface::FALLBACK_VERSION;
        }

        return $this->versionFetchingApi->version($languageResource->getResource()->getUrl());

        $version = $languageResource->getSpecificData('version', true);

        if (isset($version['version'], $version['lastSynced'])
            && null !== $version
            && (new DateTime($version['lastSynced']))->modify('+30 minutes') > new DateTime()
        ) {
            return $version['version'];
        }

        $version = $this->versionFetchingApi->version($languageResource->getResource()->getUrl());

        if (! $this->isInternalFuzzy) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
            $languageResource->addSpecificData('version', [
                'version' => $version,
                'lastSynced' => date(DateTimeInterface::RFC3339),
            ]);
            $languageResource->save();
        }

        return $version;
    }
}
