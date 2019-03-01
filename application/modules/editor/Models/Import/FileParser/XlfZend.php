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
 * Fileparsing for the Zend XLIFF files used for internal translation of translate5
 * 
 * The name is just ZendXliff/ZendXlf to distinguish it between regular Xliff and the xliff used in our Zend based application.
 * This name should not provide any connection between Zend and Xliff in general, only in the context of translate5!
 */
class editor_Models_Import_FileParser_XlfZend extends editor_Models_Import_FileParser_Xlf {
    protected $originalSourceChunks = [];
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['xlfzend','zxliff'];
    }
    
    protected function calculateMid($opener, $source) {
        //our zend xliff uses just ids instead mids, so we generate them out of the very long ids:
        //override completly the mid calculation, since we dont use subs or mrks!
        $transUnit = $this->xmlparser->getParent('trans-unit');
        return $this->shortenMid($this->xmlparser->getAttribute($transUnit['attributes'], 'id'));
    }
    
    private function shortenMid($mid) {
        return md5($mid);
    }
    
    /**
     * override to deal with the base64 long mids
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::setMid()
     */
    protected function setMid($mid) {
        $mid = explode('_', $mid);
        $segmentCount = array_pop($mid);
        $mid = $this->shortenMid(join('_', $mid));
        parent::setMid($mid.'_'.$segmentCount);
    }
    
    protected function parse() {
        //force preserve Whitespace
        $this->config = new Zend_Config($this->config->toArray(), true);
        $this->config->runtimeOptions->import->xlf->preserveWhitespace = true;
        $this->config->setReadOnly();
        
        $this->_origFile = preg_replace("/id='([^']+)'/", "id=\"$1\"", $this->_origFile);
        return parent::parse();
    }
    
    /**
     * Convert the internal HTML tags to <ph> tags
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf::extractSegment()
     */
    protected function extractSegment($transUnit) {
        foreach($this->currentTarget as $mid => $target) {
            $this->protectHtml($target);
        }
        //resetting here the original source chunks container, after protecting target, 
        // since we don't want to reset the targets, only the source ones 
        $this->originalSourceChunks = [];
        foreach($this->currentSource as $mid => $source) {
            $this->protectHtml($source);
        }
        $result = parent::extractSegment($transUnit);
        $this->unprotectHtmlInSource();
        return $result;
    }
    
    /**
     * protect the HTML content inside the trans unit as ph tag with content
     * @param string $content
     * @return string
     */
    protected function protectHtml($nodeInfo) {
        $start = $nodeInfo['opener'] + 1; //we have to exclude the <source>|<target> tags them self
        for ($i = $start; $i < $nodeInfo['closer']; $i++) {
            $chunk = $this->xmlparser->getChunk($i);
            //if it is a tag, then we replace it with a <ph> tag
            if(substr($chunk, 0, 1) == '<' && substr($chunk, -1) == '>') {
                $this->originalSourceChunks[$i] = $chunk;
                $chunk = '<x id="'.$i.'" translate5OriginalContent="'.htmlspecialchars($chunk).'"/>';
                $this->xmlparser->replaceChunk($i, $chunk);
            }
        }
    }
    
    /**
     * Before source is stored in skeleton, we have to unprotect the HTML tags, since we need the original content in the skel
     */
    protected function unprotectHtmlInSource() {
        foreach ($this->originalSourceChunks as $i => $chunk) {
            $this->xmlparser->replaceChunk($i, $chunk);
        }
    }
    
    /**
     * Just override this check to check nothing, since the zend xliff is not valid xliff here
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf::checkXliffVersion()
     */
    protected function checkXliffVersion($attributes, $key) {
    }
    
}