<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * For each mt language resources check and update if there is unsupported language code.
 * The language code is not supported when the resource language/s can not be found in
 * the resource languages list.
 * The resources languges are loaded from the remote api endpoint.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$sql = 'SELECT ll.*,lr.serviceType,lr.name,lr.serviceName, lr.resourceId FROM LEK_languageresources_languages ll
INNER JOIN LEK_languageresources lr ON ll.languageResourceId = lr.id
WHERE lr.resourceType = "mt";';

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query($sql);
$result = $res->fetchAll();

if(empty($result)){
    //nothing to migrate
    return;
}

$languagesCache=[];
/***
 * Convert from sub to mayor if the language if the sublanguage is not supported by the remote resource
 *
 * @param string $needle
 * @param array $haystack
 * @return string
 */
function convertLanguage(string $needle,array $haystack) {
    $needle = strtolower($needle);
    foreach ($haystack as &$slng){
        $slng = strtolower($slng);
    }
    $return="";
    foreach ($haystack as $source){
        if($source == $needle){
            //all good
            break;
        }
        $split = explode('-', $needle);
        $split = reset($split);
        
        if($source == $split){
            $return = $source;
            break;
        }
    }
    return $return;
}

$manager = ZfExtended_Factory::get('editor_Services_Manager');
/* @var $manager editor_Services_Manager */

foreach ($result as $res){
    echo "To check : ".$res['name']. ' Service:'.$res['serviceName'] .'<br/>'. PHP_EOL;
    if(!isset($languagesCache[$res['languageResourceId']])){
        try {
            $resource = $manager->getResourceById($res['serviceType'], $res['resourceId']);
            if(empty($resource)){
                throw new ZfExtended_Exception("Resource not found.");
            }
            //add languages to usable resources
            $connector = ZfExtended_Factory::get('editor_Services_Connector');
            /* @var $connector editor_Services_Connector */
            $languages = $connector->languages($resource);
            
            $languagesCache[$res['languageResourceId']] = $languages;
        } catch (Exception $e) {
            echo "Resource with name: ".$res['name']. ' ignored because of error. Error was:'.$e->getMessage() .'<br/>'. PHP_EOL;
            continue;
        }
    }
    
    
    $languages = $languagesCache[$res['languageResourceId']];
    $s = convertLanguage($res['sourceLangCode'], $languages[editor_Services_Connector_Abstract::SOURCE_LANGUAGES_KEY] ?? $languages);
    
    $modelLanguages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
    /* @var $modelLanguages editor_Models_LanguageResources_Languages */
    $modelLanguages->load($res['id']);
    
    if(!empty($s)){
        $model = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        $model->loadByRfc5646($s);
        
        $modelLanguages->setSourceLang($model->getId());
        $modelLanguages->setSourceLangCode($s);
        
        echo "Unsupported language found for resource: [".$res['name']."] . Old value :[".$res['sourceLangCode'].'] ; Changed to: ['.$s.']'.'<br/>'. PHP_EOL;
    }
    
    $t = convertLanguage($res['targetLangCode'], $languages[editor_Services_Connector_Abstract::TARGET_LANGUAGES_KEY] ?? $languages);
    
    if(!empty($t)){
        $model = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $model editor_Models_Languages */
        $model->loadByRfc5646($t);
        
        $modelLanguages->setTargetLang($model->getId());
        $modelLanguages->setTargetLangCode($t);
        
        echo "Match found for resource: [".$res['name']."] . Old value :[".$res['targetLangCode'].'] ; Changed to: ['.$t.']'.'<br/>'. PHP_EOL;
    }
    
    if(!empty($s) || !empty($t)){
        $modelLanguages->save();
    }
}
