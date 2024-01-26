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
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\T5memory\RecalculateRulesHashWorker;

/**
 * Part of Content protection feature. Number protection part
 * Output mapping stands for connection between regex and target language.
 * In case user wants to provide custom output format for found entry he will use this model to do so
 */
class editor_ContentprotectionoutputmappingController extends ZfExtended_RestController
{
    protected $entityClass = OutputMapping::class;

    protected $postBlacklist = ['id'];

    /**
     * @var OutputMapping
     */
    protected $entity;

    public function indexAction(): void
    {
        /** @var array{id: int, languageId: int, type: string, name: string, description: string, format: string}[] */
        $this->view->rows = $this->entity->loadAllForFrontEnd();
        $this->view->total = $this->entity->getTotalCount();
    }

    public function deleteAction()
    {
        $this->entityLoad();
        $entity = clone $this->entity;

        parent::deleteAction();

        $this->queueRecalculateRulesHashWorker((int) $entity->getLanguageId());
    }

    public function postAction()
    {
        try {
            parent::postAction();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1591' => 'You already created an {mapping} mapping for this {index} combination'
            ], 'editor.content-protection');

            throw new ZfExtended_UnprocessableEntity('E1591', ['mapping' => 'Output', 'index' => 'language-rule']);
        }

        $this->queueRecalculateRulesHashWorker((int) $this->entity->getLanguageId());
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }

    public function namecomboAction(): void
    {
        $this->view->rows = (new ContentProtectionRepository())->getContentRecognitionForOutputMappingForm();
        $this->view->total = count($this->view->rows);
    }

    private function queueRecalculateRulesHashWorker(int $languageId): void
    {
        $worker = ZfExtended_Factory::get(RecalculateRulesHashWorker::class);
        $worker->init(parameters: [
            'languageId' => $languageId,
            'direction' => RecalculateRulesHashWorker::DIRECTION_OUTPUT
        ]);
        $worker->queue();
    }
}
