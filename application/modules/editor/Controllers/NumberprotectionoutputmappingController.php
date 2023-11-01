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

use MittagQI\Translate5\NumberProtection\Model\NumberRepository;
use MittagQI\Translate5\NumberProtection\Model\OutputMapping;

/**
 * Part of Content protection feature. Number protection part
 * Output mapping stands for connection between regex and target language.
 * In case user wants to provide custom output format for found entry he will use this model to do so
 */
class editor_NumberprotectionoutputmappingController extends ZfExtended_RestController
{
    protected $entityClass = OutputMapping::class;

    protected $postBlacklist = ['id'];

    /**
     * @var OutputMapping
     */
    protected $entity;

    public function indexAction(): void
    {
        /** @var array{id: int, languageId: int, type: string, name: string, format: string}[] rows */
        $this->view->rows = $this->entity->loadAllForFrontEnd();
        $this->view->total = $this->entity->getTotalCount();
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }

    public function namecomboAction(): void
    {
        $this->view->rows = (new NumberRepository())->getNumberRecognitionForOutputMappingForm();
        $this->view->total = count($this->view->rows);
    }
}
