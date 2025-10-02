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
use editor_Models_Segment as Segment;
use MittagQI\Translate5\Integration\Contract\SegmentUpdateDtoFactoryInterface;
use MittagQI\Translate5\Integration\QueryStringProvider;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use Zend_Config;

class SegmentUpdateDtoFactory implements SegmentUpdateDtoFactoryInterface
{
    public function __construct(
        private readonly TagHandlerProvider $tagHandlerProvider,
        private readonly QueryStringProvider $queryStringProvider,
        private readonly SegmentContext $segmentContext,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TagHandlerProvider::create(),
            QueryStringProvider::create(),
            SegmentContext::create(),
        );
    }

    public function supports(LanguageResource $languageResource): bool
    {
        return \editor_Services_OpenTM2_Service::NAME === $languageResource->getServiceName();
    }

    public function getUpdateDTO(
        LanguageResource $languageResource,
        Segment $segment,
        Zend_Config $config,
        ?UpdateOptions $updateOptions,
    ): UpdateSegmentDTO {
        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $config,
        );
        $fileName = $this->getFileName($segment);
        $source = $tagHandler->prepareQuery($this->queryStringProvider->getQueryString($segment));
        $tagHandler->setInputTagMap($tagHandler->getTagMap());
        $target = $tagHandler->prepareQuery($segment->getTargetEdit(), false);
        $useSegmentTimestamp = $updateOptions?->useSegmentTimestamp;
        $userName = $segment->getUserName();
        $context = $this->segmentContext->getContext($segment);

        return new UpdateSegmentDTO(
            $source,
            $target,
            $fileName,
            $useSegmentTimestamp ? (int) $segment->getTimestamp() : time(),
            $userName,
            $context,
        );
    }

    /**
     * returns the filename to a segment
     */
    private function getFileName(Segment $segment): string
    {
        return \editor_ModelInstances::file((int) $segment->getFileId())->getFileName();
    }
}
