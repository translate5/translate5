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


use MittagQI\Translate5\Segment\TagRepair\Tags;

/**
 * Protects the translate5 internal tags as singular img tags to send them to a request based API
 * To better be able to re-establish incomplete tags paired tags are turned into singular ones (all img-tags)
 */
class editor_Services_Connector_TagHandler_HtmlRepaired extends editor_Services_Connector_TagHandler_Abstract {

    /**
     * The repair tag processors
     * @var Tags[]
     */
    private array $repairTags = [];
    private ?editor_Models_Segment $currentSegment;

    public function __construct(){
        $this->logger = ZfExtended_Factory::get('ZfExtended_Logger_Queued');
    }

    /**
     * Turns the internal tags (start & end) to simple singular img-tags to increase chances to restore them
     * Therefore a re-identifyable id must be provided
     * @param string $queryString
     * @return string
     * @throws ZfExtended_Exception
     */
    public function prepareQuery(string $queryString): string {
        if (empty($this->currentSegment)) {
            throw new ZfExtended_Exception('editor_Services_Connector_TagHandler_HtmlRepaired::prepareQuery: A currentSegment must be provided per query to use this tag-handler');
        }
        $key = 'rt'.$this->currentSegment->getId();
        try {
            // this tries to load the segment's
            $this->repairTags[$key] = new Tags($queryString);
            return $this->repairTags[$key]->getRequestHtml();
        } catch(Exception $e) {
            // if this is not possible this means the markup is heavily broken and we have no chance but to dismiss the tags
            $this->repairTags[$key] = NULL;
            return strip_tags($queryString);
        }
    }
    /**
     * restores the tags from the sent image-tags and repairs lost tags or tag fragments
     * @param string $resultString
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function restoreInResult(string $resultString): ?string {
        if (empty($this->currentSegment)) {
            throw new ZfExtended_Exception('editor_Services_Connector_TagHandler_HtmlRepaired::restoreInResult: A currentSegment must be provided per restore that identifies the query to use this tag-handler');
        }
        $this->hasRestoreErrors = false;
        $key = 'rt'.$this->currentSegment->getId();
        try {
            return $this->repairTags[$key]->recreateTags($resultString);
        } catch(Exception $e) {
            $this->hasRestoreErrors = true;
            return strip_tags($resultString);
        }
    }

    public function setCurrentSegment(editor_Models_Segment $segment)
    {
        $this->currentSegment = $segment;
    }
}
