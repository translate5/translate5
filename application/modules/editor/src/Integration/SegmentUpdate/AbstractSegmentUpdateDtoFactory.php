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

namespace MittagQI\Translate5\Integration\SegmentUpdate;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Services_Connector_TagHandler_Abstract as TagHandler;
use MittagQI\Translate5\Integration\Contract\SegmentUpdateDtoFactoryInterface;
use MittagQI\Translate5\Integration\QueryStringProvider;
use MittagQI\Translate5\LanguageResource\Adapter\TagsProcessing\TagHandlerFactory;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use Zend_Config;

abstract class AbstractSegmentUpdateDtoFactory implements SegmentUpdateDtoFactoryInterface
{
    public function __construct(
        private readonly TagHandlerFactory $tagHandlerFactory,
        protected readonly QueryStringProvider $queryStringProvider,
    ) {
    }

    public function getUpdateDTO(
        LanguageResource $languageResource,
        Segment $segment,
        Zend_Config $config,
        ?UpdateOptions $updateOptions,
    ): UpdateSegmentDTO {
        $tagHandler = $this->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $config,
        );
        $source = $tagHandler->prepareQuery($this->queryStringProvider->getQueryString($segment));
        $target = $tagHandler->prepareQuery($segment->getTargetEdit());

        return $this->composeDto($segment, $source, $target);
    }

    protected function composeDto(
        Segment $segment,
        string $source,
        string $target,
    ): UpdateSegmentDTO {
        return new UpdateSegmentDTO(
            $source,
            $target,
            '',
            time(),
            '',
            $segment->getMid(),
        );
    }

    protected function getHandlerConfigPart(): string
    {
        return 'default';
    }

    protected function getHandlerParams(Zend_Config $config): array
    {
        $sendWhitespaceAsTag = (bool) $config->runtimeOptions
            ->LanguageResources
            ->{$this->getHandlerConfigPart()}
            ?->sendWhitespaceAsTag
        ;

        return [
            TagHandler::OPTION_KEEP_WHITESPACE_TAGS => $sendWhitespaceAsTag,
        ];
    }

    protected function getTagHandler(int $sourceLang, int $targetLang, Zend_Config $config): TagHandler
    {
        $tagHandler = $this->tagHandlerFactory->createTagHandler(
            $this->getHandlerConfigPart(),
            $config,
            $this->getHandlerParams($config),
        );
        $tagHandler->setLanguages($sourceLang, $targetLang);

        return $tagHandler;
    }
}
