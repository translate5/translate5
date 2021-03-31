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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On to parse Translate5 XLF specific stuff
 */
class editor_Models_Import_FileParser_Xlf_Namespaces_Translate5 extends editor_Models_Import_FileParser_Xlf_Namespaces_Abstract{
    const TRANSLATE5_XLIFF_NAMESPACE = 'xmlns:translate5="http://www.translate5.net/"';
    
    /**
     * Internal tagmap
     * @var array
     */
    protected $tagMap = [];
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::transunitAttributes()
     */
    public function transunitAttributes(array $attributes, editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes) {
        //TODO parse:
        //trans-unit id="7" translate5:autostateId="4" translate5:autostateText="not_translated">
        settype($attributes['translate5:maxNumberOfLines'], 'integer');
        $segmentAttributes->maxNumberOfLines = $attributes['translate5:maxNumberOfLines'];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $this->tagMap = [];
        $xmlparser->registerElement('translate5:tagmap', null, function($tag, $key, $opener) use ($xmlparser){
            //get the content between the tagmap tags:
            $storedTags = $xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
            $givenTagMap = unserialize(base64_decode($storedTags));
            unset($storedTags);
            foreach($givenTagMap as $bptKey => $data) {
                $gTag = $data[0];
                $originalTag = $data[1];
                //we convert the tagMap to:
                // $this->tagMap[<g id="123">] = [<internalOpener>,<internalCloser>];
                // $this->tagMap[<x id="321">] = <internalSingle>;
                if(!empty($data[2])) {
                    $closer = $data[2];
                    $this->tagMap[$gTag] = [$originalTag, $givenTagMap[$closer][1]];
                }
                else {
                    $this->tagMap[$gTag] = $originalTag;
                }
            }
        });
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getPairedTag()
     */
    public function getPairedTag($xlfBeginTag, $xlfEndTag){
        //in the translate5 internal tag map everything is mapped by the opener only:
        return $this->getSingleTag($xlfBeginTag);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getSingleTag()
     */
    public function getSingleTag($xlfTag){
        //some foreign tools add spaces between the last attribute and the closing />
        $xlfTag = preg_replace('#"[\s]+/>$#', '"/>', $xlfTag);
        if(!empty($this->tagMap[$xlfTag])) {
            return $this->tagMap[$xlfTag];
        }
        //some tools convert <g> tag pair to just a self closing <g/> tag,
        // if we got no tagmap match we try to find a g tag without the slash then
        $xlfTag = preg_replace('#<g([^>]+)/>#', '<g$1>', $xlfTag);
        if(!empty($this->tagMap[$xlfTag])) {
            return $this->tagMap[$xlfTag];
        }
        return null;
    }
    
    /**
     * Translate5 uses x,g and bx ex tags only. So the whole content of the tags incl. the tags must be used.
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::useTagContentOnly()
     */
    public function useTagContentOnly() {
        return false;
    }
}
