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

use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\Base\SegmentProcessorInterface;

class editor_Plugins_SpellCheck_SegmentProcessor implements SegmentProcessorInterface
{

    /**
     * LanguageTool connector instance
     *
     * @var editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter[]
     */
    private static array $connector = [];

    public function __construct(private ?string $spellcheckLanguage)
    {
    }

    /**
     * Get LanguageTool connector instance
     *
     * @return editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter|null
     */
    public function getConnector($slot = null) {

        // If no $slot is given
        if (!$slot) {

            // Get connector instance with default slot
            $connector = ZfExtended_Factory::get('editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter');

            // Get that default slot
            $slot = $connector->getApiBaseUrl();

            // Put connector instance into $_connector array under $slot key for further accessibility, if not there yet
            if (!isset(self::$connector[$slot])) {
                self::$connector[$slot] = $connector;
            }
        }

        // Get connector instance for given $slot
        return self::$connector[$slot] ?? self::$connector[$slot] = ZfExtended_Factory::get('editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter', [$slot]);
    }

    /**
     * Do process
     *
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string|null $slot
     *
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     */
    public function process(array $segmentsTags, ?string $slot = null): void
    {
        /* @var editor_Models_Db_SegmentQuality $qualityM */
        $qualityM = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');

        // Get connector
        $connector = $this->getConnector($slot);

        // Get quality type
        $type = editor_Plugins_SpellCheck_QualityProvider::qualityType();

        // Foreach segment
        foreach ($segmentsTags as $tags) {

            // Get segment and task shortcut
            $segment = $tags->getSegment();

            // If existing qualities won't be marked for deletion
            if ($tags->getProcessingMode() != editor_Segment_Processing::EDIT) {

                // Clean existing spellcheck-qualities
                $qualityM->removeBySegmentAndType($segment->getId(), editor_Plugins_SpellCheck_QualityProvider::qualityType());
            }

            // Foreach target
            foreach ($tags->getTargets() as $target) {

                // Do check
                $check = new editor_Plugins_SpellCheck_Check($segment, $target->getField(), $connector, $this->spellcheckLanguage);

                // Process check results
                foreach ($check->getStates() as $category => $qualityA) {
                    foreach ($qualityA as $quality) {
                        $tags->addQuality(
                            field: $target->getField(),
                            type: $type,
                            category: $category,
                            additionalData: $quality
                        );
                    }
                }

                // Prevent other target-columns from being processed as this is not fully supported yet
                break;
            }

            // Save qualities
            $tags->getQualities()->save();
        }
    }

    /**
     * @param string $spellcheckLanguage
     */
    public function setSpellcheckLanguage(string $spellcheckLanguage): void
    {
        $this->spellcheckLanguage = $spellcheckLanguage;
    }
}
