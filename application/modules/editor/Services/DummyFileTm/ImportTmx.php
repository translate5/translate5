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
 * Imports TMX content into the internal
 */
class editor_Services_DummyFileTm_ImportTmx {
    protected $xmlParser;
    
    protected $sourceLanguage;
    
    protected $targetLanguage;
    
    protected $source;
    
    protected $target;
    
    public function import(SplFileInfo $tmxfile, editor_Models_LanguageResources_LanguageResource $langRes, Closure $importCallback) {
        $z = new XMLReader;
        $z->open($tmxfile);
        
        $this->registerXmlParser();
        
        // move to the first <product /> node
        while ($z->read() && $z->name !== 'header');
        if($z->name !== 'header') {
            //no header found
            return;
        }
        
        //TODO auto set the languages on language resource creation
        $this->targetLanguage = strtolower($z->getAttribute('adminlang'));
        $this->sourceLanguage = strtolower($z->getAttribute('srclang'));
        
        while ($z->read() && $z->name !== 'tu');
        
        
        // now that we're at the right depth, hop to the next <product/> until the end of the tree
        while ($z->name === 'tu')
        {
            $this->source = $this->target = null;
            $this->xmlParser->parse($z->readOuterXML());
            if(!is_null($this->source) && !is_null($this->target)) {
                $importCallback([
                    'languageResourceId' => $langRes->getId(),
                    'mid' => $z->getAttribute('tuid'),
                    'source' => $this->xmlParser->join($this->source),
                    'target' => $this->xmlParser->join($this->target),
                ]);
            }
            $z->next('tu');
        }
    }
    
    protected function registerXmlParser() {
        // either one should work
        $this->xmlParser = new editor_Models_Import_FileParser_XmlParser();
        
        //remove tags with content
        $this->xmlParser->registerElement('bpt,ept,it,ph', null, function($tag, $key, $opener) {
            $this->xmlParser->replaceChunk($opener['openerKey'], '', $key-$opener['openerKey']+1);
        });
            
        //remove just the tag and keep content
        $this->xmlParser->registerElement('hi', function($tag, $attributes, $key){
            $this->xmlParser->replaceChunk($key, '');
        }, function($tag, $key) {
            $this->xmlParser->replaceChunk($key, '');
        });
                
        //get the content
        $this->xmlParser->registerElement('seg', null, function($tag, $key, $opener) {
            $segment = $this->xmlParser->getRange($opener['openerKey']+1, $key - 1);
            $parent = $this->xmlParser->getParent('tuv');
            $currentLanguage = strtolower($this->xmlParser->getAttribute($parent['attributes'], 'xml:lang'));
            if($currentLanguage == $this->sourceLanguage) {
                $this->source = $segment;
            } else {
                $this->target = $segment;
            }
        });
    }
}