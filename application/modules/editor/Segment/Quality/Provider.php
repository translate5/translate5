<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Base Implementation for all Quality Providers
 *
 */
abstract class editor_Segment_Quality_Provider implements editor_Segment_TagProviderInterface {
    
    /**
     * Retrieves our type
     * @return string
     */
    public static function qualityType(){
        return static::$type;
    }
    /**
     * MUST be set in inheriting classes
     * @var string
     */
    protected static $type = NULL;

    public function __construct(){
        if(static::$type == NULL){
            throw new ZfExtended_Exception(get_class($this).' must have a ::$type property defined');
        }
    }
    /**
     * Retrieves the provider type that is a system-wide unique string to identify the provider
     * @return string
     */
    public function getType() : string {
        return static::$type;
    }
    /**
     * Quality Providers must use the same key to identify the provider & all of it's tags
     * {@inheritDoc}
     * @see editor_Segment_TagProviderInterface::getTagType()
     */    
    public function getTagType() : string {
        return static::$type;
    }
    /**
     * Retrieves if the provider has an own import worker
     * If this API returns false the import is processed via ::processSegment
     * @return boolean
     */
    public function hasImportWorker() : bool {
        return false;
    }
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     */
    public function addImportWorker(editor_Models_Task $task, int $parentWorkerId) {
  
    }
    /**
     * Processes the Segment and it's tags for the editing (which is unthreaded)
     * Note: the return value is used for further processing so it might even be possible to create a new tags-object though this is highly unwanted
     * @param editor_Models_Task $task
     * @param editor_Segment_Tags $tags
     * @param bool $forImport
     * @return editor_Segment_Tags
     */
    public function processSegment(editor_Models_Task $task, editor_Segment_Tags $tags, bool $forImport) : editor_Segment_Tags {
        return $tags;
    }

    public function isSegmentTag(string $type, string $nodeName, array $classNames, array $attributes) : bool {
        return false;
    }

    public function createSegmentTag(int $startIndex, int $endIndex, string $nodeName, array $classNames) : editor_Segment_Tag {
        return new editor_Segment_AnyTag($startIndex, $endIndex);
    }
}
