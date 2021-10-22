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
 * An Interface that Classes must implement to act as a tag provider
 * A Tag Provider acts as a Provider for editor_Segment_TagCreator to provide classes representing tags in segment-contents
 */
interface editor_Segment_TagProviderInterface {
    
    /**
     * Must return a unique to identify all tags that ::createSegmentTag may return
     * @return string
     */
    public function getTagType() : string;
    /**
     * Evaluates, if the Dom-Tag with the passed props match the providers quality tag
     * @param string $type
     * @param string $nodeName
     * @param array $classNames
     * @param array $attributes
     * @return bool
     */
    public function isSegmentTag(string $type, string $nodeName, array $classNames, array $attributes) : bool;
    /**
     * Evaluates, if the Dom-Tag will show up in the exported content#
     * This means, these tags may be further processed (like internal tags) so the name may better be 'isPreExportedTag'
     * @return bool
     */
    public function isExportedTag() : bool;
    /**
     * Creates a segment tag. Will only be called, when ::isSegmentTag evaluates "true"
     * @param int $startIndex
     * @param int $endIndex
     * @param string $nodeName
     * @param array $classNames
     * @return editor_Segment_Tag
     */
    public function createSegmentTag(int $startIndex, int $endIndex, string $nodeName, array $classNames) : editor_Segment_Tag;
    /**
     * Retrieves a CSS class, that identifies the tag (may not be the only identifying prop, but all of the tags have this class) or NULL, if the quality has no related tag
     * @return string
     */
    public function getTagIndentificationClass() : ?string;
    /**
     * Retrieves the node-name of the related segment tag or NULL, if the quality has no related tag
     * @return string
     */
    public function getTagNodeName() : ?string;    
}
