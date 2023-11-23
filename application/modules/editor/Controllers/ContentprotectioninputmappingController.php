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

use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;

/**
 * Part of Content protection feature. Number protection part
 * Input mapping stands for connection between regex and source language
 */
class editor_ContentprotectioninputmappingController extends ZfExtended_RestController
{
    protected $entityClass = InputMapping::class;

    protected $postBlacklist = ['id'];

    /**
     * @var InputMapping
     */
    protected $entity;

    public function putAction()
    {
        parent::putAction();
    }

    public function indexAction(): void
    {
        /** @var array{id: int, languageId: int, type: string, name: string, priority: int}[] rows */
        $this->view->rows = $this->entity->loadAllForFrontEnd();

        foreach ($this->view->rows as &$row) {
            $row['ruleEnabled'] = boolval($row['enabled']);
            unset($row['enabled']);
        }
        $this->view->total = $this->entity->getTotalCount();
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }

    public function namecomboAction(): void
    {
        $this->view->rows = (new ContentProtectionRepository())->getContentRecognitionForInputMappingForm();
        $this->view->total = count($this->view->rows);
    }
}
