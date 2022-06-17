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
 * Encapsulates the tagging of groups of segment-tags
 * This enables to not "misuse" the import/analysis worker for processing a single tag when editing
 */
class editor_Plugins_SpellCheck_SegmentProcessor {

    /**
     * LanguageTool connector instance
     *
     * @var editor_Plugins_SpellCheck_LanguageTool_Connector
     */
    protected static $_connector = null;

    /**
     * 
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string $slot
     * @param bool $doSaveTags
     * @throws ZfExtended_Exception
     */
    public function process(array $segmentsTags, string $slot = null, $processingMode) {

        foreach ($segmentsTags as $tags) { /* @var $tags editor_Segment_Tags */

            $segmentId = $tags->getSegmentId();

            // Get segment and task shortcut
            $segment = $tags->getSegment(); $task = $tags->getTask();

            //
            $spellCheckLang = $this->getConnector($slot)->getSpellCheckLangByTaskTargetLangId($task->getTargetLang());

            // Distinct states
            $states = [];

            // Foreach target
            foreach ($tags->getTargets() as $target) { /* @var $target editor_Segment_FieldTags */

                // Do check
                $check = new editor_Plugins_SpellCheck_Check($task, $target->getField(), $segment, $this->getConnector(), $spellCheckLang);

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
            }

            $tags->getQualities()->save();
        }
    }

    /**
     * Get LanguageTool connector instance
     *
     * @return mixed|null
     */
    public function getConnector() {
        return self::$_connector ?? self::$_connector = ZfExtended_Factory::get('editor_Plugins_SpellCheck_LanguageTool_Connector');
    }
}
