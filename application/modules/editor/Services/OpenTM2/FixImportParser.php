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
 * Parser to modify TMX data before importing to OpenTM2 to fix several issues
 * - replace it|ph|ept|bpt tags with content with same tag as single tag,
 *   since content is irrelevant for translate5 and tags with content are not matched
 *   against single tags always provided by translate5
 * - remove type attributes, since tags with a type attribute may not match (beeing replaced) against the tags provided by translate5
 */
class editor_Services_OpenTM2_FixImportParser {
    const ATTRIBUTES_TO_KEEP = ['i' => null, 'x' => null];
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xml;
    
    public function __construct() {
        $this->xml = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->setupParser();
    }
    
    protected function setupParser() {
        $this->xml->registerElement('tu > tuv > seg it, tu > tuv > seg ph, tu > tuv > seg bpt, tu > tuv > seg ept', null,
            function(string $tag, int $idx, array $opener){
                //<ph type="lb"/> is kept, since represents a line break which is handled as such
                if($tag == 'ph' && $this->xml->getAttribute($opener['attributes'], 'type') == 'lb') {
                    $tag = '<ph type="lb"/>';
                }
                else {
                    //clean up attributes, so that only i and x remain.
                    $attributes = array_intersect_key($opener['attributes'], self::ATTRIBUTES_TO_KEEP);
                    $tag = '<'.$tag;
                    foreach($attributes as $key => $value) {
                        $tag .= ' '.$key.'="'.$value.'"';
                    }
                    $tag .= '/>';
                }
                //replace tag with sanitized one
                $this->xml->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($idx-$opener['openerKey']+1));
                $this->xml->replaceChunk($opener['openerKey'], $tag);
            }
        );
    }
    
    public function convert(string $tmxData): ?string {
        return $this->xml->parse($tmxData);
    }
}