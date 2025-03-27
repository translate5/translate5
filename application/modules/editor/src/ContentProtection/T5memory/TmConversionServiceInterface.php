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

namespace MittagQI\Translate5\ContentProtection\T5memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;

interface TmConversionServiceInterface
{
    public function setRulesHash(LanguageResource $languageResource, int $sourceLanguageId, int $targetLangId): void;

    public function isTmConverted(int $languageResourceId): bool;

    public function isConversionInProgress(int $languageResourceId): bool;

    public function startConversion(int $languageResourceId): void;

    public function convertT5MemoryTagToContent(string $string): string;

    /**
     * @param array<string, string[]> $numberTagMap
     */
    public function convertContentTagToT5MemoryTag(
        string $queryString,
        bool $isSource,
        array &$numberTagMap = []
    ): string;

    public function convertTMXForImport(string $filenameWithPath, int $sourceLangId, int $targetLangId): string;
}
