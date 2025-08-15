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

namespace MittagQI\Translate5\LanguageResource\Adapter\TagsProcessing;

use editor_Services_Connector_TagHandler_Abstract;
use editor_Services_Connector_TagHandler_HtmlRepaired;
use editor_Services_Connector_TagHandler_PairedTags;
use editor_Services_Connector_TagHandler_Remover;
use editor_Services_Connector_TagHandler_T5MemoryXliff;
use editor_Services_Connector_TagHandler_Xliff;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Factory as Factory;

class TagHandlerFactory
{
    public function __construct(
        private readonly Zend_Config $config,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('config'),
        );
    }

    public function createTagHandler(string $resourceAlias, array $params = [], ?Zend_Config $config = null): editor_Services_Connector_TagHandler_Abstract
    {
        $config = $config ?? $this->config;
        $configuredHandler = $config->runtimeOptions->LanguageResources->{$resourceAlias}?->tagHandler ?? null;

        // Using Factory::get here for backwards compatibility as there might be overwritten tag handlers
        return match ($configuredHandler) {
            'xliff' => Factory::get(editor_Services_Connector_TagHandler_Xliff::class, [$params]),
            'html_image' => Factory::get(editor_Services_Connector_TagHandler_HtmlRepaired::class, [$params]),
            't5memoryxliff' => Factory::get(editor_Services_Connector_TagHandler_T5MemoryXliff::class, [$params]),
            'xliff_paired_tags' => Factory::get(editor_Services_Connector_TagHandler_PairedTags::class, [$params]),
            default => Factory::get(editor_Services_Connector_TagHandler_Remover::class, [$params]),
        };
    }
}
