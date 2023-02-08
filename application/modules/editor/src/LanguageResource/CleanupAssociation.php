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

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_LanguageResources_CustomerAssoc;
use Zend_Db_Table_Exception;
use ZfExtended_BadGatewayErrorCode;
use ZfExtended_ErrorCodeException;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_UnprocessableEntity;
use ZfExtended_Utils;

/**
 * Check and clean the resource/pivot associations on a task when customer is removed from the language resource.
 *
 */
class CleanupAssociation
{

    /***
     * @param array $removedCustomers customers which should be removed when language resource is edited
     */
    public function __construct(private array $removedCustomers, private int $languageResourceId)
    {
    }

    /***
     * Get the conflicting association by given entity class name (TaskAssociation or TaskPivotAssociation)
     *
     * @param string $entityClass
     * @return array
     */
    private function getConflictByEntity(string $entityClass): array
    {
        if (empty($this->removedCustomers)){
            return [];
        }

        $assoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $customerAssocs = $assoc->loadCustomerIds($this->languageResourceId);

        $deleteAll = ZfExtended_Utils::isArrayEqual($customerAssocs,$this->removedCustomers);

        // if all customers are removed, this means it is delete request and based on this, we remove all associations
        // based on those customers
        if( $deleteAll === false){

            $removed = array_diff($customerAssocs,$this->removedCustomers);
            // in case we remove all
            if (empty($removed)){
                return [];
            }
        }else{
            $removed = $this->removedCustomers;
        }

        $taskAssoc = ZfExtended_Factory::get($entityClass);
        return $taskAssoc->getAssociatedByCustomer($removed,$this->languageResourceId);
    }

    /***
     * Get all task associations which should be removed if the customer is unassigned from the resource
     * @return array
     * @throws Zend_Db_Table_Exception
     */
    private function getConflictTaskAssoc(): array
    {
        return $this->getConflictByEntity(TaskAssociation::class);
    }

    /***
     * Get all task pivot associations which shouldbe removed if the customer is unassigned from the resource
     * @return array
     */
    private function getConflictTaskPivotAssoc(){
        return $this->getConflictByEntity(TaskPivotAssociation::class);
    }

    /***
     * Check if for the removed customers there is already task associations. If yes, this will throw an exception
     * with the assigned tasks listed
     * @return void
     * @throws Zend_Db_Table_Exception
     * @throws \ZfExtended_ErrorCodeException
     */
    public function check(){
        $taskAssocs = $this->getConflictTaskAssoc();
        if( !empty($taskAssocs)){
            $taskNames = array_column($taskAssocs,'taskName');
            $this->throwException($taskNames);
        }
        $taskPivotAssocs = $this->getConflictTaskPivotAssoc();
        if( !empty($taskPivotAssocs)){
            $taskNames = array_column($taskPivotAssocs,'taskName');
            $this->throwException($taskNames);
        }
    }

    /***
     * Clean the conflicting task association for the removed customers
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    public function cleanAssociation(): void
    {
        $taskAssocs = $this->getConflictTaskAssoc();
        if( !empty($taskAssocs)){
            $ids = array_column($taskAssocs,'id');
            /** @var TaskAssociation $taskAssoc */
            $taskAssoc = ZfExtended_Factory::get(TaskAssociation::class);
            $taskAssoc->deleteByIds($ids);
        }

        $taskPivotAssocs = $this->getConflictTaskPivotAssoc();
        if( !empty($taskPivotAssocs)){
            $ids = array_column($taskPivotAssocs,'id');
            /** @var TaskPivotAssociation $taskAssoc */
            $taskAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
            $taskAssoc->deleteByIds($ids);
        }
    }

    /***
     * @param array $taskNames
     * @return mixed
     * @throws \ZfExtended_ErrorCodeException
     */
    private function throwException(array $taskNames){
        ZfExtended_UnprocessableEntity::addCodes([
            'E1447' => 'This resource is assigned to a task via the removed customer.',
        ], 'languageresource');

        throw ZfExtended_UnprocessableEntity::createResponse('E1447',[
            'errorMessages' => [
                'Die entfernten Kunden werden in den folgenden Aufgaben verwendet:',
                'Wenn Sie diese Kunden entfernen, wird die Zuordnung dieser Sprachressource zu den Aufgaben dieser Kunden aufgehoben. Möchten Sie die Zuweisungen aufheben? Nur dann können Sie die Kunden aus dieser Sprachressource hier in der Sprachressourcenverwaltung entfernen.'
            ]
        ],extraData: [
            'taskList' => $taskNames
        ]);
    }

}