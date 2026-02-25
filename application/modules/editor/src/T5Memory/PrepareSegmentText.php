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
use editor_Models_Segment_InternalTag;
use MittagQI\Translate5\T5Memory\Contract\TagHandlerProviderInterface;
use MittagQI\Translate5\T5Memory\TagHandler\PassThroughTagHandlerProvider;
use MittagQI\Translate5\T5Memory\TagHandler\TagHandlerProvider;

class PrepareSegmentText
{
    public function __construct(
        private readonly TagHandlerProviderInterface $tagHandlerProvider,
        private readonly editor_Models_Segment_InternalTag $internalTag,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TagHandlerProvider::create(),
            new editor_Models_Segment_InternalTag(),
        );
    }

    public static function createForDelete(): self
    {
        return new self(
            PassThroughTagHandlerProvider::create(),
            new editor_Models_Segment_InternalTag(),
        );
    }

    /**
     * @return array{string, string}
     */
    public function prepareText(
        LanguageResource $languageResource,
        string $source,
        string $target,
        \Zend_Config $config,
    ): array {
        $source = $this->internalTag->replace(
            $source,
            fn ($match) => str_replace(\editor_Models_Segment_InternalTag::IGNORE_CLASS, '', $match[0]),
        );
        $target = $this->internalTag->replace(
            $target,
            fn ($match) => str_replace(\editor_Models_Segment_InternalTag::IGNORE_CLASS, '', $match[0]),
        );

        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $config,
        );
        $source = $tagHandler->prepareQuery($source);
        $tagHandler->setInputTagMap($tagHandler->getTagMap());
        $target = $tagHandler->prepareQuery($target, false);

        return [$source, $target];
    }
}
