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

namespace MittagQI\Translate5\Segment\TagRepair;

/**
 * Helper class that processes a single String with the automatic tag-repair
 * It is tailored to work with HTML Markup, that is processed with a Service based translation
 * It will presumably not work with G-tags from a xliff etc.
 * This is a use-once class only that expects to be used sequentially
 */
class HtmlProcessor {

    /**
     * @var Tags
     */
    private Tags $tagRepair;

    /**
     * Retrieves the HTML to be used for requesting the service API
     * @param string $html
     * @return string
     * @throws \ZfExtended_Exception
     */
    public function prepareRequest(string $html) : string {
        $this->tags = new Tags($html);
        return $this->tags->getRequestHtml();
    }

    /**
     * Reverts the returned markup & repairs possible tag-losses or syntactically incorrect markup
     * As a Fallback, the markup is simply removed
     * @param string $resultHtml
     * @return string
     */
    public function restoreResult(string $resultHtml) : string {
        try {
            return $this->tags->recreateTags($resultHtml);
        } catch (Exception $e) {
            return strip_tags($resultHtml);
        }
    }
}
