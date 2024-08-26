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

namespace MittagQI\Translate5\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Generator;
use MittagQI\Translate5\CrossSynchronization\Dto\AdditionalInfoViewData;

interface SynchronisationInterface
{
    /**
     * @return SynchronisationType[]
     */
    public function syncSourceOf(): array;

    /**
     * @return SynchronisationType[]
     */
    public function syncTargetFor(): array;

    /**
     * Determines if the service allows multiple sources for synchronization
     */
    public function isOneToOne(): bool;

    /**
     * @param int|null $customerId Use null if the synchronization is not customer specific
     * @return Generator<array{source: string, target: string}|null>
     */
    public function getSyncData(
        LanguageResource $languageResource,
        LanguagePair $languagePair,
        SynchronisationType $synchronisationType,
        ?int $customerId = null,
    ): Generator;

    public function queueCustomerSynchronisation(CrossSynchronizationConnection $connection, int $customerId): void;

    public function queueDefaultSynchronisation(CrossSynchronizationConnection $connection): void;

    public function cleanupOnCustomerRemovedFromConnection(CrossSynchronizationConnection $connection, int $customerId): void;

    public function cleanupDefaultSynchronisation(LanguageResource $source, LanguageResource $target): void;

    public function getAdditionalInfoViewData(
        CrossSynchronizationConnection $connection,
        LanguageResource $languageResource
    ): AdditionalInfoViewData;
}
