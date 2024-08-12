<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Terminology\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Generator;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\CrossSynchronizationConnection;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\LanguagePair;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationInterface;
use MittagQI\Translate5\LanguageResource\CrossSynchronization\SynchronisationType;
use MittagQI\Translate5\Terminology\TermCollectionRepository;
use MittagQI\Translate5\Tools\CharCleanup;

class SynchronisationService implements SynchronisationInterface
{
    public function __construct(
        private TermCollectionRepository $termCollectionRepository
    ) {
    }

    public static function create(): self
    {
        return new self(new TermCollectionRepository());
    }

    public function syncSourceOf(): array
    {
        return [SynchronisationType::Glossary];
    }

    public function syncTargetFor(): array
    {
        return [];
    }

    public function isOneToOne(): bool
    {
        return false;
    }

    public function getSyncData(
        LanguageResource $languageResource,
        LanguagePair $languagePair,
        SynchronisationType $synchronisationType,
        ?int $customerId = null,
    ): Generator {
        $terms = $this->termCollectionRepository
            ->getTermTranslationsForLanguageCombo(
                (int) $languageResource->getId(),
                $languagePair->sourceId,
                $languagePair->targetId
            );

        foreach ($terms as $item) {
            $item['source'] = CharCleanup::cleanTermForMT($item['source']);
            $item['target'] = CharCleanup::cleanTermForMT($item['target']);

            if (empty($item['source']) || empty($item['target'])) {
                continue;
            }

            yield [
                'source' => $item['source'],
                'target' => $item['target'],
            ];
        }
    }

    public function queueConnectionSynchronisation(CrossSynchronizationConnection $connection): void
    {
        // as of now we don't have functionality of terms synchronization
    }

    public function queueDefaultSynchronisation(LanguageResource $source, LanguageResource $target): void
    {
        // as of now we don't have functionality of terms synchronization
    }

    public function cleanupOnConnectionDeleted(CrossSynchronizationConnection $deletedConnection): void
    {
        // as of now we don't have functionality of terms synchronization
    }

    public function cleanupDefaultSynchronisation(LanguageResource $source, LanguageResource $target): void
    {
        // as of now we don't have functionality of terms synchronization
    }
}
