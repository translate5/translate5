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

use MittagQI\Translate5\NumberProtection\Model\NumberRecognition;
use MittagQI\Translate5\NumberProtection\Protector\IPAddressProtector;
use MittagQI\Translate5\NumberProtection\Protector\MacAddressProtector;

/**
 * Part of Content protection feature. Number protection part
 * In Number Recognition controller we have list of regexes of corresponding types
 * Regexes are used to find content for protection in text
 */
class editor_NumberprotectionnumberrecognitionController extends ZfExtended_RestController
{
    protected $entityClass = NumberRecognition::class;

    protected $postBlacklist = ['id'];

    public function indexAction()
    {
        foreach ($this->entity->loadAll() as $row) {
            $this->fixRowTypes($row);
            $data[] = $row;
        }

        /** @var array{
         *     id: int,
         *     type: string,
         *     name: string,
         *     description: string,
         *     regex: string,
         *     matchId: int,
         *     format: string,
         *     isDefault: bool,
         *     keepAsIs: bool,
         *     priority: int,
         *     rowEnabled: bool
         * } rows
         */
        $this->view->rows = $data;
        $this->view->total = $this->entity->getTotalCount();
    }

    public function putAction()
    {
        parent::putAction();

        if (!empty($this->view->rows)) {
            $this->fixRowTypes($this->view->rows);
        }
    }

    private function fixRowTypes(object|array &$row): void
    {
        $row = (array) $row;
        $row['keepAsIs'] = boolval($row['keepAsIs']);
        if (in_array($row['type'], [MacAddressProtector::getType(), IPAddressProtector::getType()])) {
            $row['keepAsIs'] = true;
        }
        $row['rowEnabled'] = boolval($row['enabled']);
        unset($row['enabled']);
        $row['isDefault'] = boolval($row['isDefault']);
    }

    protected function decodePutData()
    {
        parent::decodePutData();
        $this->data = (array) $this->data;
        if (array_key_exists('rowEnabled', $this->data)) {
            $this->data['enabled'] = $this->data['rowEnabled'];
            if (in_array($this->data['type'], [MacAddressProtector::getType(), IPAddressProtector::getType()])) {
                $this->data['keepAsIs'] = true;
            }
            unset($this->data['rowEnabled']);
        }
    }

    /**
     * @var NumberRecognition
     */
    protected $entity;

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }
}
