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
 * Check and clean the resource/pivot associations on a task when customer is removed from the language resource.
 *
 */
class Customer extends Base
{
    /**
     * @param int $languageResourceId
     * @param array $customersLeft: The customers that shall remain as assocs
     */
    public function __construct(protected int $languageResourceId, protected array $customersLeft)
    {
    }

    /***
     * Get the conflicting association by given entity class name (TaskAssociation or TaskPivotAssociation)
     *
     * @param string $entityClass
     * @return array
     */
    protected function getConflictByEntity(string $entityClass): array
    {

        $assoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $customerAssocs = $assoc->loadCustomerIds($this->languageResourceId);

        $hasChange = ZfExtended_Utils::isArrayEqual($customerAssocs, $this->customersLeft) === false;
        if (!$hasChange) {
            return [];
        }

        $toRemove = array_diff($customerAssocs, $this->customersLeft);
        if (empty($toRemove)) {
            return [];
        }
        $taskAssoc = ZfExtended_Factory::get($entityClass);
        return $taskAssoc->getAssociatedByCustomer($toRemove, $this->languageResourceId);
    }


    /***
     * @param array $taskNames
     * @return void
     * @throws \ZfExtended_ErrorCodeException
     */
    protected function throwException(array $taskNames): void
    {
        ZfExtended_UnprocessableEntity::addCodes([
            'E1447' => 'This resource is assigned to a task via the removed customer.',
        ], 'languageresource');

        throw ZfExtended_UnprocessableEntity::createResponse('E1447', [
            'errorMessages' => [
                'Die entfernten Kunden werden in den folgenden Aufgaben verwendet:',
                'Wenn Sie diese Kunden entfernen, wird die Zuordnung dieser Sprachressource zu den Aufgaben dieser Kunden aufgehoben. Möchten Sie die Zuweisungen aufheben? Nur dann können Sie die Kunden aus dieser Sprachressource hier in der Sprachressourcenverwaltung entfernen.'
            ]
        ], extraData: [
            'taskList' => $taskNames
        ]);
    }
}