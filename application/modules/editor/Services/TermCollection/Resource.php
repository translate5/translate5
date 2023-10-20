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

use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;

class editor_Services_TermCollection_Resource extends editor_Models_LanguageResources_Resource {
    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
        $this->analysable = true;//is used by match analysis
        $this->writable = false; //single terms can not be updated 
        $this->type = editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION;
    }

    /**
     * @param array|null $specificData
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return array{status:string,statusInfo:string}
     * @throws Zend_Exception
     */
    public function getInitialStatus(?array $specificData, ZfExtended_Zendoverwrites_Translate $translate): array
    {
        return [
            'status' => LanguageResourceStatus::NOTCHECKED,
            'statusInfo' => '' // no addtional info here
        ];
    }
}
