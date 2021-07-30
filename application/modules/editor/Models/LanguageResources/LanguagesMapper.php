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
 * Mapping between remote language resource languages and translate5 lek_languages values.
 */
class editor_Models_LanguageResources_LanguagesMapper {
    
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    
    /***
     * Language collection from all available languages in LEK_languages table
     * where the array key is the language rfc value
     *
     * @var array
     */
    protected $languageCollection = [];
    
    
    protected $missing = [];
    
    public function __construct() {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }
    
    /***
     * Try to find an existing lek_languages language for each $resourceLangauge.
     * The resource languages will be compared against rfc5646 values(translate5 works with rfc5646 values).
     * @param array $resourceLangauge
     * @return array
     */
    public function map(array $resourceLangauge) {
        $this->resetMissing();
        if(empty($this->languageCollection)){
            $model = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $model editor_Models_Languages */
            $this->languageCollection = $model->loadAllKeyCustom('rfc5646',true);
        }
        $result = [];
        foreach ($resourceLangauge as $code){
            $lowerCode = strtolower($code);
            if(isset($this->languageCollection[$lowerCode])){
                $lang = $this->languageCollection[$lowerCode];
                $name = $this->translate->_($lang['langName']);
                $result[$name] = [$lang['id'], $name.' ('.$code.')', $lang['rtl'],$code];
                continue;
            }
            //try to find a mayor language
            $lowerCode=explode('-',$lowerCode);
            $lowerCode = reset($lowerCode);
            
            if(!isset($this->languageCollection[$lowerCode])){
                $this->missing[]=$code;
                continue;
            }
            $lang = $this->languageCollection[$lowerCode];
            $name = $this->translate->_($lang['langName']);
            $result[$name] = [$lang['id'], $name.' ('.$code.')', $lang['rtl'],$code];
        }
        ksort($result); //sort by name of language
        return array_values($result);
    }
    
    protected function resetMissing() {
        $this->missing = [];
    }
    
    /***
     * Return missing languages after the mapping is done
     * @return array
     */
    public function getMissing() {
        return $this->missing;
    }
}

