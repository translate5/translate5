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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * OpenTM2 HTTP Connection API
 */
class editor_Services_OpenTM2_FixLanguageCodes {

    /**
     * The mapping of language keys.
     * WARNING: if adding a language here, add it in function tmxOnDownload too!
     * In tmxOnDownload the concrete values send by OpenTM2 to be replaced back must be tested out.
     * @var array
     */
    protected $languageMap = [
        //langcode_search => langcode_replace
        'mn'    => 'ru',
        'mn-MN' => 'ru',
        'hi'    => 'ar',
        'hi-IN' => 'ar',
        'fr-CH' => 'fr',
        'it-CH' => 'it',
    ];
    
    /**
     * Must contain search and replace language keys
     * @var array
     */
    protected $labels = [
        //langcode_search => langcode_replace
        'mn'    => 'Mongolian',
        'mn-MN' => 'Mongolian',
        'ru'    => 'Russian',
        'ru-RU' => 'Russian',
        'ar'    => 'Arabic',
        'hi'    => 'Hindi',
        'hi-IN' => 'Hindi',
        'fr'    => 'French(national)',
        'fr-FR' => 'French(national)',
        'fr-CH' => 'French(Swiss)',
        'it'    => 'Italian',
        'it-IT' => 'Italian',
        'it-CH' => 'Italian(Swiss)',
    ];
    
    /**
     * @param string $key
     * @return string
     */
    public function key(string $key): string {
        if(empty($this->languageMap[$key])) {
            return $key;
        }
        return $this->languageMap[$key];
    }
    
    /**
     * Applies several fixes to the uploaded TM data
     * @param string $tmxData
     * @return string
     */
    public function tmxOnUpload(string $tmxData): string {
        //fix datatype from unknown to plaintext
        $tmxData = preg_replace('#(<header[^>]+)datatype="unknown"([^>]+>)#i', '${1}datatype="plaintext"${2}', $tmxData, 1);
        $tmxData = preg_replace_callback('#(<header[^>]+)srclang="([^"]*)"([^>]+>)#i', function($matches){
            return $matches[1].'srclang="'.($this->languageMap[$matches[2]] ?? $matches[2]).'"'.$matches[3];
        }, $tmxData);
        
        $search = [];
        $replace = [];
        $format = 'xml:lang="%s"';
        foreach($this->languageMap as $key => $value) {
            $search[] = sprintf($format, $key);
            $replace[] = sprintf($format, $value);
        }
        return str_replace($search, $replace, $tmxData);
    }
    
    /**
     * Replaces language usages in the downloaded TM
     * @param string $sourceLang the source language key as configured in translate5 for the language resource
     * @param string $targetLang the target language key as configured in translate5 for the language resource
     * @param string $tmxData
     * @return string
     */
    public function tmxOnDownload($sourceLang, $targetLang, $tmxData) {
        
        $search = [];
        $replace = [];
        
        if(!empty($this->languageMap[$sourceLang])) {
            
            $tmxData = preg_replace('#(<header[^>]+)srclang="[^"]*"([^>]+>)#i', '${1}srclang="'.$sourceLang.'"${2}', $tmxData, 1);
            
            $sourceLangMapped = $this->languageMap[$sourceLang];
            $search[] = 'xml:lang="'.$sourceLangMapped.'"';
            //OpenTM2 returns sometimes the language as "fr" and sometimes as "fr-FR",
            // so we replace just both:
            $search[] = 'xml:lang="'.$sourceLangMapped.'-'.strtoupper($sourceLangMapped).'"';
            $search[] = '<prop type="tmgr:language">'.($this->labels[$sourceLangMapped] ?? '').'</prop>';
            $replace[] = 'xml:lang="'.$sourceLang.'"';
            $replace[] = 'xml:lang="'.$sourceLang.'"';
            $replace[] = '<prop type="tmgr:language">'.($this->labels[$sourceLang] ?? '').'</prop>';
            //source original labels
        }
        
        if(!empty($this->languageMap[$targetLang])) {
            $targetLangMapped = $this->languageMap[$targetLang];
            $search[] = 'xml:lang="'.$targetLangMapped.'"';
            //OpenTM2 returns sometimes the language as "fr" and sometimes as "fr-FR",
            // so we replace just both:
            $search[] = 'xml:lang="'.$targetLangMapped.'-'.strtoupper($targetLangMapped).'"';
            $search[] = '<prop type="tmgr:language">'.strtoupper($this->labels[$targetLangMapped] ?? '').'</prop>';
            $replace[] = 'xml:lang="'.$targetLang.'"';
            $replace[] = 'xml:lang="'.$targetLang.'"';
            $replace[] = '<prop type="tmgr:language">'.strtoupper($this->labels[$targetLang] ?? '').'</prop>';
            //target uppercase labels
        }
        
        return str_replace($search, $replace, $tmxData);
    }
}