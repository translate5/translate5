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

use MittagQI\Translate5\Plugins\SpellCheck\Base\Enum\SegmentState;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Worker\AbstractImport;

/**
 * The Worker to process the spellchecking for an import
 */
class editor_Plugins_SpellCheck_Worker_Import extends AbstractImport
{
    /**
     * Prefix for workers resource-name
     *
     * @var string
     */
    protected static $praefixResourceName = 'SpellCheck_';

    /**
     * @var editor_Plugins_SpellCheck_Configuration
     */
    private $config;

    /**
     * Language that will be passed as a param within LanguageTool-request along with segment text for spellcheck
     *
     * @var string|null
     */
    private ?string $spellCheckLang = null;

    /**
     * Spell checking takes approximately 15 % of the import time
     *
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 15;
    }

    protected function setParams(array $parameters): void
    {
        // Get language to be passed within LanguageTool-request params (if task target language is supported)
        $this->spellCheckLang = $parameters['spellCheckLang'];
    }

    protected function getConfiguration(): editor_Plugins_SpellCheck_Configuration
    {
        return $this->config ?? $this->config = new editor_Plugins_SpellCheck_Configuration();
    }

    protected function getMalfunctionStateRecheck(): string
    {
        return SegmentState::SEGMENT_STATE_RECHECK;
    }

    protected function getMalfunctionStateDefect(): string
    {
        return SegmentState::SEGMENT_STATE_DEFECT;
    }

    protected function getProcessor(): editor_Plugins_SpellCheck_SegmentProcessor
    {
        return new editor_Plugins_SpellCheck_SegmentProcessor($this->spellCheckLang);
    }

    protected function getMetaColumnName(): string
    {
        return 'spellcheckState';
    }
}
