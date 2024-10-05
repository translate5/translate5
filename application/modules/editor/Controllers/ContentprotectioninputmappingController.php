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
use MittagQI\Translate5\ContentProtection\T5memory\RecalculateRulesHashWorker;

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
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $prevMassage = $e->getPrevious()->getMessage();

            ZfExtended_UnprocessableEntity::addCodes([
                'E1591' => 'You already created an {mapping} mapping for this {index} combination',
            ], 'editor.content-protection');

            if (strpos($prevMassage, $this->entity->getLanguageId() . '-' . $this->entity->getContentRecognitionId())) {
                throw ZfExtended_UnprocessableEntity::createResponse(
                    'E1591',
                    [
                        'contentRecognitionId' => [
                            'Sie verwenden diese Regel bereits für diese Sprache',
                        ],
                    ],
                    [
                        'mapping' => 'Input',
                        'index' => 'language-rule',
                    ]
                );
            }

            if (strpos($prevMassage, $this->entity->getLanguageId() . '-' . $this->entity->getPriority())) {
                throw ZfExtended_UnprocessableEntity::createResponse(
                    'E1591',
                    [
                        'priority' => [
                            'Regel mit dieser Priorität existiert bereits',
                        ],
                    ],
                    [
                        'mapping' => 'Input',
                        'index' => 'language-priority',
                    ]
                );
            }

            throw $e;
        }

        $this->queueRecalculateRulesHashWorker((int) $this->entity->getLanguageId());
    }

    public function putAction()
    {
        parent::putAction();

        if (array_key_exists('priority', (array) $this->data)) {
            $this->queueRecalculateRulesHashWorker((int) $this->entity->getLanguageId());
        }
    }

    public function indexAction(): void
    {
        /** @phpstan-ignore-next-line */
        $this->view->rows = $this->entity->loadAllForFrontEnd();

        /** @var array{id: int, languageId: int, type: string, name: string, description: string, priority: int, enabled: bool} $row */
        foreach ($this->view->rows as &$row) {
            $row['ruleEnabled'] = boolval($row['enabled']);
            unset($row['enabled']);
        }
        $this->view->total = $this->entity->getTotalCount();
    }

    private function queueRecalculateRulesHashWorker(int $languageId): void
    {
        $worker = ZfExtended_Factory::get(RecalculateRulesHashWorker::class);
        $worker->init(parameters: [
            'languageId' => $languageId,
            'direction' => RecalculateRulesHashWorker::DIRECTION_INPUT,
        ]);
        $worker->queue();
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }

    public function namecomboAction(): void
    {
        $this->view->rows = ContentProtectionRepository::create()->getContentRecognitionForInputMappingForm();
        $this->view->total = count($this->view->rows);
    }
}
