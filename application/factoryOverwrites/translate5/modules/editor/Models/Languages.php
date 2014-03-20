<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 2.0
 *
 */
/**
 * Ersetzt editor Languages Object Portalspezifisch, da im Portal verfügbare Sprachen in der /application/languages.ini definiert sind
 * Im Import Vorgang werden die Sprachen per rfc5646 referenziert,
 * diese angepasste Klasse befüllt die angefragte Sprache mit Daten aus der languages.ini und befüllt die LEK_languages Tabelle
 */
class factoryOverwrites_translate5_modules_editor_Models_Languages extends editor_Models_Languages {
    /**
     * @var array languages enthält alle zugelassenen Sprachen per application/languages.ini
     */
    protected $languages = array();
    /**
     * lädt die Sprache anhand dem übergebenen Sprachkürzel (nach RFC5646)
     * Wenn die Sprache nicht existiert, wird versucht diese aus der languages.ini zu erstellen
     * @param mixed $lang
     */
    public function loadLang($lang){
        parent::loadLang($lang);
        if(empty($this->row)){
            $this->languages = $this->getLanguages();
            $this->makeLanguage($lang);
        }
        return $this->row;
    }

    protected function makeLanguage($language) {
        $lang = strtoupper($language);
        if(!$this->checkLang($lang)){
            //this->row = null triggert im import einen Fehler, so soll es in diesem Fall auch sein
            return $this->row = null;
        }
        $this->init();
        $this->setLcid(null);
        $this->setLangName($this->languages[$lang]);
        $lang = preg_split('/[_-]/', $lang);
        $this->setRfc5646($this->toRfc5646($lang));
        try {
            $this->save();
        }
        catch(Exception $e) {
            $msg = 'Es wurde versucht die nachfolgende Sprache der LEK_languages automatisch anhand des languages.ini Eintrages mit index "'.$language.'" hinzuzufügen.';
            $msg .= "\n\n".print_r($this->row->toArray(), 1)."\n\n Der folgende Fehler ist dabei beim Speichern aufgetreten:\n";
            $msg .= $e->getMessage();
            throw new Exception($msg, 0, $e);
        }
        return $this->row;
    }

    protected function toUnix(array $lang){
        $lang[0] = strtolower($lang[0]);
        if(!empty($lang[1])){
            $lang[1] = strtoupper($lang[1]);
        }
        return join('_', $lang);
    }

    protected function toRfc5646(array $lang){
        $lang[0] = strtolower($lang[0]);
        if(!empty($lang[1])){
            $lang[1] = strtolower($lang[1]);
        }
        return join('-', $lang);
    }
    /**
     *
     * Gibt alle zugelassenen Sprachen per application/languages.ini zurück
     * 
     * - Format array('EN'=>'English',...)
     * 
     * @return array $langsArr
     */
    protected function getLanguages()
    {
        $langFile = APPLICATION_PATH .'/languages.ini';
        $langOverwritesFile = APPLICATION_PATH .'/iniOverwrites/'.APPLICATION_AGENCY.
                '/languages.ini';
        if(file_exists($langOverwritesFile)){
            $langFile = $langOverwritesFile;
        }
        $langs = new Zend_Config_Ini($langFile);
        $langsArr = $langs->languages->toArray();

        foreach($langsArr as $key => &$val){
            $upperKey = strtoupper($key);
            if($key != $upperKey){
                $langsArr[$upperKey] = $val;
                unset($langsArr[$key]);
            }
        }
        return $langsArr;
    }
    /**
     *
     * Prüft, ob das übergebene Sprachkürzel zugelassen ist
     * 
     * @return boolean
     */
    protected function checkLang(string $lang)
    {
        if(isset($this->languages[$lang]))return true;
        throw new Zend_Exception('The language shortcut '.$lang.' is not defined in languages.ini.');
    }
}