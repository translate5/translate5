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

use MittagQI\Translate5\ContentProtection\SupportsContentProtectionInterface;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\TaskTm\Operation\CreateTaskTmOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\SupportsTaskTmInterface;

/**
 * T5memory / T5Memory Service Base Class
 *
 * IMPORTANT: see the doc/comments in MittagQI\Translate5\Service\T5Memory
 */
class editor_Services_T5Memory_Service extends editor_Services_ServiceAbstract implements SupportsTaskTmInterface, SupportsContentProtectionInterface
{
    public const NAME = 'T5Memory';

    public const DEFAULT_COLOR = 'aaff7f';

    protected $resourceClass = editor_Services_T5Memory_Resource::class;

    /**
     * URL to confluence-page
     * @var string
     */
    protected static $helpPage = 'https://confluence.translate5.net/display/BUS/T5Memory';

    public function isConfigured(): bool
    {
        // since tmprefix and showMultiple100PercentMatches have workable defaults
        // (which evaulates to empty) there is no need to test them
        return $this->isConfigSet($this->config->runtimeOptions->LanguageResources->t5memory->server);
    }

    protected function embedService(): void
    {
        $urls = $this->config->runtimeOptions->LanguageResources->t5memory->server;
        $this->addResourceForeachUrl($this->getName(), $urls->toArray());
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getCreateTaskTmOperation(): CreateTaskTmOperation
    {
        return CreateTaskTmOperation::create();
    }

    public function getTmConversionService(): TmConversionService
    {
        return TmConversionService::create();
    }
}
