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

namespace MittagQI\Translate5\T5Memory\Factory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\T5Memory\Contract\TagHandlerProviderInterface;
use MittagQI\Translate5\T5Memory\DTO\DeleteSegmentDTO;
use MittagQI\Translate5\T5Memory\PrepareSegmentText;
use MittagQI\Translate5\T5Memory\TagHandler\TagHandlerProvider;
use Zend_Config;

class DeleteSegmentDtoFactory
{
    public function __construct(
        private readonly PrepareSegmentText $prepareSegmentText,
        private readonly TagHandlerProviderInterface $tagHandlerProvider,
    ) {
    }

    public static function create(): self
    {
        return new self(
            PrepareSegmentText::createForDelete(),
            TagHandlerProvider::create(),
        );
    }

    public function supports(LanguageResource $languageResource): bool
    {
        return \editor_Services_T5Memory_Service::NAME === $languageResource->getServiceName();
    }

    public function getDeleteDTO(
        LanguageResource $languageResource,
        array $segment,
        Zend_Config $config,
    ): DeleteSegmentDTO {
        [$source, $target] = $this->prepareSegmentText->prepareText(
            $languageResource,
            $segment['source'],
            $segment['target'],
            $config,
        );

        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $config,
        );

        $source = $tagHandler->prepareQuery($source, true);
        $target = $tagHandler->prepareQuery($target, false);

        return new DeleteSegmentDTO(
            $source,
            $target,
            $segment['metaData']['author'],
            $segment['metaData']['timestamp'],
            $segment['metaData']['documentName'],
            $segment['metaData']['context'],
        );
    }
}
