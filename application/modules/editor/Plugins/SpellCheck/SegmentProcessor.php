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

/**
 *
 */
class editor_Plugins_SpellCheck_SegmentProcessor {

    /**
     * LanguageTool connector instance
     *
     * @var editor_Plugins_SpellCheck_LanguageTool_Connector
     */
    protected static $_connector = [];

    /**
     * Get LanguageTool connector instance
     *
     * @return editor_Plugins_SpellCheck_LanguageTool_Connector|null
     */
    public function getConnector($slot = null) {

        // If no $slot is given
        if (!$slot) {

            // Get connector instance with default slot
            $connector = ZfExtended_Factory::get('editor_Plugins_SpellCheck_LanguageTool_Connector');

            // Get that default slot
            $slot = $connector->getApiBaseUrl();

            // Put connector instance into $_connector array under $slot key for further accessibility, if not there yet
            if (!self::$_connector[$slot]) self::$_connector[$slot] = $connector;
        }

        // Get connector instance for given $slot
        return self::$_connector[$slot] ?? self::$_connector[$slot] = ZfExtended_Factory::get('editor_Plugins_SpellCheck_LanguageTool_Connector', [$slot]);
    }

    /**
     * Do process
     *
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string|null $slot
     * @param $processingMode
     * @param bool|string $spellCheckLang
     */
    public function process(array $segmentsTags, string $slot = null, $processingMode, $spellCheckLang = false) {

        // Get connector
        $connector = $this->getConnector($slot);

        // Foreach segment
        foreach ($segmentsTags as $tags) { /* @var $tags editor_Segment_Tags */

            // Get segment and task shortcut
            $segment = $tags->getSegment();

            // Foreach target
            foreach ($tags->getTargets() as $target) { /* @var $target editor_Segment_FieldTags */

                // Do check
                $check = new editor_Plugins_SpellCheck_Check($segment, $target->getField(), $connector, $spellCheckLang);

                // Process check results
                foreach ($check->getStates() as $category => $qualityA) {
                    foreach ($qualityA as $quality) {
                        $tags->addQuality(
                            field: $target->getField(),
                            type: editor_Plugins_SpellCheck_QualityProvider::qualityType(),
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
}
