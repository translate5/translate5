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

namespace MittagQI\Translate5\Test\Fixtures;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguage;
use editor_Services_OpenTM2_Service;
use MittagQI\Translate5\Repository\LanguageRepository;

/**
 * @codeCoverageIgnore
 */
class LanguageResourceFixtures
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            LanguageRepository::create(),
        );
    }

    public function createT5MemoryLanguageResource(
        string $sourceLangRfc,
        string $targetLangRfc,
    ): LanguageResource {
        $languageResource = new LanguageResource();
        $className = explode('\\', static::class);
        $className = array_pop($className);
        $languageResource->setName($className . '-' . $sourceLangRfc . '-' . $targetLangRfc . '-' . (new \DateTime())->format('Y-m-d H:i:s'));
        $languageResource->setResourceId('editor_Services_OpenTM2_1');
        $languageResource->setResourceType('tm');
        $languageResource->setServiceType('editor_Services_OpenTM2');
        $languageResource->setServiceName(editor_Services_OpenTM2_Service::NAME);
        $languageResource->setLangResUuid(\ZfExtended_Utils::uuid());

        $languageResource->save();

        $sourceLang = $this->languageRepository->findByRfc5646($sourceLangRfc);
        $targetLang = $this->languageRepository->findByRfc5646($targetLangRfc);

        $languageResourceLanguage = new LanguageResourceLanguage();
        $languageResourceLanguage->setLanguageResourceId((int) $languageResource->getId());
        $languageResourceLanguage->setSourceLang((int) $sourceLang->getId());
        $languageResourceLanguage->setSourceLangCode($sourceLangRfc);
        $languageResourceLanguage->setTargetLang((int) $targetLang->getId());
        $languageResourceLanguage->setTargetLangCode($targetLangRfc);

        $languageResourceLanguage->save();

        return $languageResource;
    }
}
