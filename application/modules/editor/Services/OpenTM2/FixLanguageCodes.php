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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * OpenTM2 HTTP Connection API
        //FIXME in FixImportParser: fixing that only languages are imported which are supported by the language resource.
        // 1. get the language and its fuzzy languages from the corresponding language resource
        // 2. filter the tu's by the second tuv xml:lang attribute, which must be one of the fuzzy languages
 
 */
class editor_Services_OpenTM2_FixLanguageCodes {

    protected bool $disabled = false;

    /**
     * The mapping of language keys.
     * In tmxOnDownload the concrete values send by OpenTM2 to be replaced back must be tested out.
     * @var array
     */
    protected array $languageMap = [
        //langcode_search => langcode_replace
        'mn'    => 'ru',
        'mn-MN' => 'ru',
        'hi'    => 'ar',
        'hi-IN' => 'ar',
        'fr-CH' => 'fr',
        'it-CH' => 'it'
    ];

    /**
     * The mapping of language keys when exporting Memory.
     * WARNING: if adding a language here, add it in function tmxOnDownload too!
     * In tmxOnDownload the concrete values send by OpenTM2 to be replaced back must be tested out.
     * TODO: this is temp fix and it will be remove with t5 memory
     * @var array
     */
    protected array $languageMapExport = [
        'mn'    => 'ru',
        'mn-MN' => 'ru',
        'hi'    => 'ar',
        'hi-IN' => 'ar',
        'fr-CH' => 'fr',
        'it-CH' => 'it',
        'en-GB' => 'en-UK',
        'sr-Latn-ME' => 'ME',
        'bs-Latn-BA' => 'BA',
    ];
    
    /**
     * @param string $key
     * @return string
     */
    public function key(string $key): string {
        if($this->disabled || empty($this->languageMap[$key])) {
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
        if($this->disabled) {
            return $tmxData;
        }
        //fix datatype from unknown to plaintext
        //if file is utf-16, convert it first to utf-8 and check, if it is utf-16
        //TODO improve that and make iconv only if it was detected as utf-16
        //FIXME use unix file command if available
        $tmxData_utf16 = @iconv('utf-16','utf-8',$tmxData);
        if(preg_match('#^<\?xml[^>]*encoding="utf-16"[^>]*\?>#i',$tmxData_utf16)){
            $tmxData = $tmxData_utf16;
            $tmxData = preg_replace('#^(<\?xml[^>]*encoding=")utf-16("[^>]*\?>)#i','${1}utf-8${2}', $tmxData, 1);
        }
        unset($tmxData_utf16);
        $tmxData = preg_replace('#(<header[^>]+)datatype="unknown"([^>]*>)#i', '${1}datatype="plaintext"${2}', $tmxData, 1);
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
    public function tmxOnDownload(string $sourceLang, string $targetLang, string $tmxData): string {
        if($this->disabled) {
            return $tmxData;
        }
        $search = [];
        $replace = [];
        
        if(!empty($this->languageMapExport[$sourceLang])) {
            
            $tmxData = preg_replace('#(<header[^>]+)srclang="[^"]*"([^>]+>)#i', '${1}srclang="'.$sourceLang.'"${2}', $tmxData, 1);
            
            $sourceLangMapped = $this->languageMapExport[$sourceLang];
            $search[] = 'xml:lang="'.$sourceLangMapped.'"';
            //OpenTM2 returns sometimes the language as "fr" and sometimes as "fr-FR",
            // so we replace just both:
            $search[] = 'xml:lang="'.$sourceLangMapped.'-'.strtoupper($sourceLangMapped).'"';
            $replace[] = 'xml:lang="'.$sourceLang.'"';
            $replace[] = 'xml:lang="'.$sourceLang.'"';
        }

        if(!empty($this->languageMapExport[$targetLang])) {
            $targetLangMapped = $this->languageMapExport[$targetLang];
            $search[] = 'xml:lang="'.$targetLangMapped.'"';
            //OpenTM2 returns sometimes the language as "fr" and sometimes as "fr-FR",
            // so we replace just both:
            $search[] = 'xml:lang="'.$targetLangMapped.'-'.strtoupper($targetLangMapped).'"';
            $replace[] = 'xml:lang="'.$targetLang.'"';
            $replace[] = 'xml:lang="'.$targetLang.'"';
        }

        //it may happen that source lang is empty, so we set that (its the first tuv after a prop)
        $tmxData = preg_replace('#</prop>(\s+<tuv[^>]+)xml:lang=""#', '</prop>\1xml:lang="'.$sourceLang.'"', $tmxData);

        //and if targetLang is en-GB, then remaining empty xml:langs were en-UK and must be changed to en-GB
        if(strtolower($targetLang) === 'en-gb') {
            $tmxData = preg_replace('#(<tuv[^>]+)xml:lang=""#', '\1xml:lang="en-GB"', $tmxData);
        }

        //Since the prop type tmgr:language is openTM2 proprietary, we just remove it:
        $tmxData = preg_replace('#<prop type="tmgr:language">[^<]+</prop>(\s)*#', '', $tmxData);

        return str_replace($search, $replace, $tmxData);
    }

    public function setDisabled(bool $disable) {
        $this->disabled = $disable;
    }
}