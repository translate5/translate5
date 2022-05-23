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
 * See TRANSLATE-2835 - fixing TMX export from OpenTM2
 */
class editor_Services_OpenTM2_FixExport {
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xml;

    private int $changeCount = 0;

    private int $newLineCount = 0;

    public function __construct() {
        $this->xml = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->setupParser();
    }

    protected function setupParser() {

        $this->xml->registerOther(function($other, $key) {
            $replaced = htmlentities($other, flags: ENT_XML1, double_encode: false);
            if($other !== $replaced) {
                $this->changeCount++;
                $tu = $this->xml->getParent('tu');
                if(!is_null($tu)) {
                    // the TU must exist, otherwise we are outside of a TU (like <?xml header)
                    error_log('TMX Export - TU: '.$this->xml->getChunk($tu['openerKey'])."\n  OLD: ".$other."\n  NEW: ".$replaced);
                    $this->xml->replaceChunk($key, $replaced);
                }
            }
        });

        $this->xml->registerElement('seg x, seg g, seg bx, seg ex', function($tag, $attr, $key, $isSingle) {
            //if rid is an empty string, remove the attribute
            if(array_key_exists('rid', $attr) && $attr['rid'] === '') {
                unset($attr['rid']);
            }
            //convert mid to id, if id is existing throw mid away
            if(array_key_exists('mid', $attr)) {
                if(!array_key_exists('id', $attr)) {
                    $attr['id'] = $attr['mid'];
                }
                unset($attr['mid']);
            }

// FIXME Es sei denn, du fügst in editor_Tag eine methode "addAttributes" hinzu, addAttribute gibts ja:
//$tag = editor_Tag::create('x');
//$tag->addAttributes([foo => bar]);
//echo $tag->render(); (bearbeitet)

            $tag = ['<', $tag];
            foreach($attr as $k => $v) {
                $tag[] = ' '.$k.'="'.$v.'"';
            }
            if($isSingle) {
                $tag[] = '/>';
            }
            else {
                $tag[] = '>';
            }
            $this->xml->replaceChunk($key, join('', $tag));
        });

        $this->xml->registerElement('ph', function($tag, $attr, $key, $isSingle) {
            if($isSingle && $this->xml->getAttribute($attr, 'type') === 'lb') {
                $this->newLineCount++;
                $this->xml->replaceChunk($key, "\n");
            }
        });
    }
    
    public function convert(string $tmxData): string {
        $tmxTags = [
            // the valid TMX tags
            'body', 'header', 'map', 'note', 'prop', 'seg', 'tmx', 'tu', 'tuv', 'ude', 'bpt', 'ept', 'hi', 'it', 'ph', 'sub', 'ut',
            // additionally the XLF tags stored in the TMs - they are converted correctly in t5memory import then
            'x','g','bx', 'ex',
        ];
        return $this->xml->parse($tmxData, validTags: $tmxTags);
    }

    /**
     * returns the count of how many entities were replaced
     * @return int
     */
    public function getChangeCount(): int
    {
        return $this->changeCount;
    }

    /**
     * returns the count of how many ph type lb were replaced to newlines
     * @return int
     */
    public function getNewLineCount(): int
    {
        return $this->newLineCount;
    }
}