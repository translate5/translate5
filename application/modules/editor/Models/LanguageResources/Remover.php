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

/**
 * LanguageResource Remover - to separate delete-logic from LanguageResourceController, this is all encapsulated in this class.
 * So the function can be used from all places inside the application.
 */

use MittagQI\Translate5\LanguageResource\CleanupAssociation;

class editor_Models_LanguageResources_Remover {
    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $entity;
    
    /**
     * Sets the languageresource to be removed from system
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    public function __construct(editor_Models_LanguageResources_LanguageResource $languageResource) {
        $this->entity = $languageResource;
    }
    
    /**
     * Removes a languageResource completely
     */
    public function remove($forced = false, $deleteInResource = false) {
        // if the current entity is term collection, init the entity as term collection
        if($this->entity->isTc()){
            $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $collection editor_Models_TermCollection_TermCollection */
            $collection->init($this->entity->toArray());
            $this->entity = $collection;
        }
    
        //encapsulate the deletion in a transaction to rollback if for example the real file based resource can not be deleted
        $this->entity->db->getAdapter()->beginTransaction();
        try {
            $entity = clone $this->entity;
            $this->checkOrCleanAssociation($forced, $this->entity->getCustomers() ?? []);
            //delete the entity in the DB
            $this->entity->delete();
            
            // if there are any services connected to this language-resource, they also must be deleted.
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $connector = $manager->getConnector($entity);
            
            //try to delete the resource via the connector
            $deleteInResource && $connector->delete();
            //if this is successful we commit the DB delete
            $this->entity->db->getAdapter()->commit();
        }
        catch (Exception $e) {
            //if not we rollback and throw the original exception
            $this->entity->db->getAdapter()->rollBack();
            throw $e;
        }
        
        // will this remover can also be called somewhere in the code OUTSIDE a controller,
        // we have to send an event which informs about the removing / deleting of the languageResource.
        $events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [__CLASS__]);
        $events->trigger('afterRemove', $this, ['entity' => $this->entity]);
    }
    
    /**
     * Check of clean associations when customer is changed.
     * @TODO: same function exists in LanguageresourceinstanceController !!!
     * @param bool $clean
     * @return void
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    private function checkOrCleanAssociation(bool $clean, array $customerIds): void
    {
        $assocClean = ZfExtended_Factory::get(CleanupAssociation::class, [
            $customerIds,
            $this->entity->getId()
        ]);
        
        $clean ? $assocClean->cleanAssociation() : $assocClean->check();
    }}
