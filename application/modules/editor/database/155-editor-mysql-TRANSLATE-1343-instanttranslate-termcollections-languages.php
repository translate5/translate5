<?php
/*
--
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

/*
  README:
    Move all available languages in the term collection to the languageresources languages table.
    Each available language per termCollection(languageResource) will exist as unique source target combination
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

//check for terms with null term collection
$db = Zend_Db_Table::getDefaultAdapter();
$s=$db->select()->from('LEK_terms',array('id'))->where('collectionId IS NULL');
$result=$db->fetchAll($s);
if(!empty($result)){
    $result=array_column($result, 'id');

    //create the term collection which will hold the terms without termcollection
    $collection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
    /* @var $collection editor_Models_TermCollection_TermCollection */
    $collection->setName('Lost and Found Terms');
    
    $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
    /* @var $service editor_Services_TermCollection_Service */
    $nsp=$service->getServiceNamespace();
    $collection->setResourceId($nsp);
    $collection->setServiceType($nsp);
    $collection->setServiceName($service->getName());
    $collection->setColor($service::DEFAULT_COLOR);
    $resourceId=$collection->save();
    
    //add the terms to the new termcollection
    $termDb=ZfExtended_Factory::get('editor_Models_Db_Terms');
    /* @var $termDb editor_Models_Db_Terms */
    $data = array(
        'collectionId'      => $resourceId
    );
    $where = $termDb->getAdapter()->quoteInto('id IN(?)', $result);
    $termDb->update($data, $where);
}

$model=ZfExtended_Factory::get('editor_Models_Term');
/* @var $model editor_Models_Term */
$model->updateAssocLanguages();