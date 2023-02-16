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

namespace MittagQI\Translate5\LanguageResource\CleanupAssociation;

use editor_Models_LanguageResources_CustomerAssoc;
use ZfExtended_Factory;
use ZfExtended_UnprocessableEntity;
use ZfExtended_Utils;

/**
 *
 */
class Task extends Base
{

    /***
     * Get the conflicting association by given entity class name (TaskAssociation or TaskPivotAssociation)
     *
     * @param string $entityClass
     * @return array
     */
    protected function getConflictByEntity(string $entityClass): array
    {
        $taskAssoc = ZfExtended_Factory::get($entityClass);
        return $taskAssoc->getAssociatedByResource($this->languageResourceId);
    }

    protected function throwException(array $taskNames): void
    {
        ZfExtended_UnprocessableEntity::addCodes([
            'E1473' => 'This resource is assigned to a task',
        ], 'languageresource');

        throw ZfExtended_UnprocessableEntity::createResponse('E1473', [
            'errorMessages' => [
                'Die zu löschende Sprachressource wird in den folgenden Aufgaben verwendet:',
                'Wenn sie entfernt wird, wird die Zuweisung der Sprachressource von allen oben genannten Aufgaben entfernt.
Möchten Sie die Zuweisungen entfernen und die Sprachresource löschen?'
            ]
        ], extraData: [
            'taskList' => $taskNames
        ]);
    }
}