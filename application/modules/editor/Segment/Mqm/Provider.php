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
 * Adds the Segment Quality Entries for the MQM tags that may have been added (by the user only)
 */
class editor_Segment_Mqm_Provider extends editor_Segment_Quality_Provider
{
    protected static $type = editor_Segment_Tag::TYPE_MQM;

    protected static $segmentTagClass = 'editor_Segment_Mqm_Tag';

    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return ($qualityConfig->enableMqmTags == 1);
    }

    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode): editor_Segment_Tags
    {
        if ($qualityConfig->enableMqmTags == 1) {
            foreach ($tags->getTagsByType(static::$type) as $mqmTag) {
                /* @var $mqmTag editor_Segment_Mqm_Tag */
                // this will also update the ext-js sequence-id in the tag with the database-id of the bound quality in case of an extJs generated ID
                $tags->addQualityByTag($mqmTag);
            }
        }

        return $tags;
    }

    public function translateType(ZfExtended_Zendoverwrites_Translate $translate): ?string
    {
        return $translate->_('Manuelle QS (im Segment)');
    }

    public function translateCategory(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        ?editor_Models_Task $task
    ): ?string {
        // see editor_Segment_Mqm_Tag::createCategoryVal how we evaluate the index from the category
        $mqmIndex = intval(str_replace(editor_Segment_Tag::TYPE_MQM . '_', '', $category));
        $mqmType = $task ? editor_Segment_Mqm_Configuration::instance($task)->getMqmTypeForId($mqmIndex) : null;

        if (null !== $mqmType) {
            // currently, there are no translations for the usually english names.
            // To change, use: return $translate->_($mqmType);
            return $mqmType;
        }

        // not worth an exception, should not happen if configuration correct
        return 'UNKNOWN MQM-TYPE-ID ' . $mqmIndex;
    }

    public function getAllCategories(?editor_Models_Task $task): array
    {
        if (null === $task) {
            return [];
        }

        return array_map(
            fn ($id) => editor_Segment_Mqm_Tag::createCategoryVal($id),
            editor_Segment_Mqm_Configuration::instance($task)->getAllIds()
        );
    }

    public function isExportedTag(): bool
    {
        return true;
    }
}
