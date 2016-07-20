<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside
 
 Copyright (c) 2014:
    Original Version: beo Gesellschaft für Sprachen und Technologie mbH; 
    Changes and extensions: Marc Mittag, MittagQI - Quality Informatics;
    All rights reserved.

 Contact: http://www.beo-doc.de/; http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

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
 
 @copyright  beo Gesellschaft für Sprachen und Technologie mbH; Marc Mittag, MittagQI - Quality Informatics
 @author     beo Gesellschaft für Sprachen und Technologie mbH; MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/** #@+
 * @author Michael Herrnberger, beo GmbH; Marc Mittag - MittagQI
 * @package editor
 * @version 1.0
 */

class editor_Plugins_Transit_File{
    const HEADER_FILTER = "FFD";
    const HEADER_SECURE_SEG = "SecureSeg";
    const HEADER_HISTORY_OPT = "HistoryOpt";
    
    const UNICODE_BOM_LE = "\xFF\xFE";
    const UNICODE_BOM_BE = "\xFE\xFF";
    
    public static $encoding = "UTF-8";          // Zeichen-Kodierung mit der die Daten an die Funktionen übergeben werden
    
    private $segments = array ();
    private $filename = "";
    private $fullpath = "";
    private $save_count = 0;
    private $save_date = "";
    private $save_time = "";
    private $filter_uid = "";
    private $header = "";
    private $ischanged = false;
    /**
     *
     * @var DOMDocument
     */
    private $file;
    private $file_xpath;
    
    
    /**
     * Opens an existing transit-language or -referenz file. (xml-file)
     * 
     * @param String $file contents of filename (both of them are passed for compliance of beo-transit-classes with standard translate5 fileparser and and less filesystem interaction)
     * @param String $filename
     * 
     * @return TRUE if ok, else throw new Zend_Exception
     *       
     */
    public function open(string $file, string $filename){
        $this->file = new DOMDocument();
        //$this->file = new DOMDocument("1.0", "UTF-16LE");
        $this->file->preserveWhiteSpace = true;
        $this->file->substituteEntities = false;
        
        try {
            $res = $this->file->loadXML($file, LIBXML_PARSEHUGE);
        }
        catch (Exception $e) {
            throw new Zend_Exception( "file $filename produces the following error on opening as XML: ".$e->getMessage());
        }
        if (!$res){
            throw new Zend_Exception( "file $filename could not be loaded as xml document.");
        }
        
        $this->filename = basename($filename);
        $this->fullpath = $filename;
        
        $this->file_xpath = new DOMXpath($this->file);
        
        $version = $this->getNodeValue("/Transit/@version");
        if ($version != "4.0"){
            throw new Zend_Exception( "Datei $filename ist keine TransitNXT-Datei.");
        }
        
        $node_header = $this->getNode("/Transit/Header");
        if (! $this->parseheader($node_header)){
            throw new Zend_Exception("Transit-Header von $filename ist beschädigt/ungültig.");
        }
        
        $node_body = $this->getNode("/Transit/Body");
        if (! $this->parsebody($node_body)){
            throw new Zend_Exception("Transit-Body von $filename ist beschädigt/ungültig.");
        }
        
        /* we do not need this here, has the editor_Models_Import_FileParser_Transit
         * takes care of this
         * 
         * if ($this->getSegmentCount() == 0){
                throw new Zend_Exception("Datei $filename enthält keine Segmente.");
        }*/
        return (true);
    
    } // end of function "open"
    
    private function getNodeValue($query){
        $value = $this->file_xpath->evaluate("string(" . $query . ")");
        return $value;
    }
    
    private function getNode($query){
        $nodes = $this->file_xpath->query($query);
        if ($nodes->length > 0)
        {
            return $nodes->item(0);
        }
        return FALSE;
    }
    
    private function getNodeList($query){
        $nodes = $this->file_xpath->query($query);
        return $nodes;
    }
    
    /**
     * saves $this->file into file $this->fullpath
     * segments in $this->segments are replaced in original file if segment has changed
     * 
     * @return boolean
     * @throws Zend_Exception
     */
    public function save(){
        if(!file_put_contents($this->fullpath, $this->getAsString())){
            throw new Zend_Exception("The file ", $this->fullpath . " could not be saved.");
        }
        return true;
    }
    
    /**
     * segments in $this->segments are replaced in original file if segment has changed
     * 
     */
    public function getAsString() {
        $nodes_segment = $this->getNodeList("//Seg");
        
        foreach ($nodes_segment as $node_segment){
            $segID = $node_segment->getAttribute("SegID");
            $segment = $this->segments[$segID];
            if ($segment->isChanged()){
                $node_segment->setAttribute("Data", $segment->getData());
                $node_segment->nodeValue = "";
                $text = (string)$segment->getText();
                if($text === ''){
                    $node_segment->textContent = '';
                }
                else{
                    $newTextXML = $this->file->createDocumentFragment();
                    $newTextXML->appendXML($text);
                    $node_segment->appendChild($newTextXML);
                }
            }
        }
        
        $this->file->encoding = "UTF-16";
        
        $fileAsString = $this->file->saveXML();
        return $this->codeCleaner($fileAsString);
    }
    
    
    private function parseBody($node_body){
        if (! $node_body->hasChildNodes()){
            return FALSE;
        }
        
        $nodes_segment = $this->getNodeList("//Seg");
        
        foreach ( $nodes_segment as $node_segment ){
            if (! $this->segmentAdd($node_segment)){
                throw new Zend_Exception("Segment " . count($this->segments) . " von Datei $this->filename ist beschädigt.");
            }
        }
        return TRUE;
    }
    
    
    private function parseHeader($node_header){
        if (! $node_header->hasChildNodes()){
            return FALSE;
        }
        
        foreach ( $node_header->childNodes as $node ){
            if ($node->nodeType != XML_ELEMENT_NODE){
                continue;
            }
            
            switch ($node->nodeName){
                case self::HEADER_FILTER :
                    $ffd = $node->getAttribute("GUID");
                    if (! empty($ffd)){
                        $this->filter_uid = self::ucs2enc($ffd);
                    }
                    else{
                        throw new Zend_Exception("FFD-GUID fehlt.");
                    }
                    break;
                case self::HEADER_SECURE_SEG :
                    break;
                case self::HEADER_HISTORY_OPT :
                    break;
                default :
                    throw new Zend_Exception("Unbekannter Header-Eintrag in Datei {$this->filename}: " . $node->nodeName);
                    break;
            }
        }
        
        if (! empty($ffd)){
            return TRUE;
        }
        
        return FALSE;
    } // end of function parseheader
    
    
    public function getSegmentCount(){
        return count($this->segments);
    }
    
    public function getSegment($nr){
        return $this->segments [$nr];
    }
    
    public function getSegments(){
        return $this->segments;
    }
    
    public function getFilterUID(){
        return $this->filter_uid;
    }
    
    public function getSaveCount(){
        return $this->save_count;
    }
    
    public function getSaveDate(){
        return $this->save_date;
    }
    
    public function getSaveTime(){
        return $this->save_time;
    }
    
    public function getFilename(){
        return $this->filename;
    }
    
    public function getFullpath(){
        return $this->fullpath;
    }
    
    public function isChanged(){
        foreach ( $this->segments as $segment ){
            if ($segment->isChanged()){
                return TRUE;
            }
        }
        return FALSE;
    }
    
    public function segmentAdd($node_segment){
        $segment = ZfExtended_Factory::get('editor_Plugins_Transit_Segment');
        
        $id = $segment->init($node_segment);
        if (! $id){
            return FALSE;
        }
        
        $this->segments [$id] = $segment;
        return TRUE;
    }
    
    public static function ucs2enc($text){
        return iconv("UTF-16LE", self::$encoding, $text);
    }
    
    public static function enc2ucs($text){
        $text = iconv(self::$encoding, "UTF-16LE", $text);
        
        // Wenn die ersten zwei Zeichen dem Unicode BOM entsprechen, dann löschen
        if (substr($text, 0, 2) == self::UNICODE_BOM_LE || substr($text, 0, 2) == self::UNICODE_BOM_BE)
        {
            $text = substr($text, 2);
        }
        return $text;
    }
    /**
     * clean transit code before save to regain original kind of xml - and not
     * the one generated by php
     * 
     * @global type $targetFiles
     */
    protected function codeCleaner(string $file){
        // used closure callback-functions
        $callbackTagcontent = function($match){
            if (strpos($match[2], $this->MBEncode("<")) === false){
                $temp_return = $match[1] . str_replace ($this->MBEncode('"'), $this->MBEncode('&quot;'), $match[2]) . $match[3];
                return $temp_return;
            }

            $callbackTagcontentText = function($submatch){
                $temp_subreturn = str_replace ($this->MBEncode('"'), $this->MBEncode('&quot;'), $submatch[1]) . $submatch[2];
                return $temp_subreturn;
            };

            $matchExpression = '(.*?)(<.*?>)';
            $nonsenseTag = $this->MBEncode("<MQI_NONSENSTAG />");
            $temp_replaced = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontentText, $match[2].$nonsenseTag);
            $temp_replaced = mb_ereg_replace($nonsenseTag, "", $temp_replaced);
            $temp_return = $match[1] . $temp_replaced . $match[3];

            return $temp_return;
        };

        mb_regex_encoding("UTF-16LE");
        mb_regex_set_options ("m");

        $matchExpression = '(<Tag .*?>)(.*?)(<\/Tag>)';
        $file = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontent, $file);
        
        $matchExpression = '(<FontTag .*?>)(.*?)(<\/FontTag>)';
        $file = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontent, $file);
        
        $matchExpression = '(<Tab .*?>)(.*?)(<\/Tab>)';
        $file = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontent, $file);
        
        $matchExpression = '(<NU .*?>)(.*?)(<\/NU>)';
        $file = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontent, $file);

        $matchExpression = '(<Seg .*?>)(.*?)(<\/Seg>)';
        $file = mb_ereg_replace_callback($this->MBEncode($matchExpression), $callbackTagcontent, $file);
        
        $file = mb_ereg_replace($this->MBEncode('(<Seg [^<>]*)(\/>)'), '\\1'.$this->MBEncode('></Seg>'), $file);
        $file = mb_ereg_replace($this->MBEncode('(<SubSeg [^<>]*)(\/>)'), '\\1'.$this->MBEncode('></Seg>'), $file);
        //convert whitespace between segments back to \r\n
        $file = mb_ereg_replace($this->MBEncode(">\n<"),$this->MBEncode(">\r\n<"), $file);
        return  mb_ereg_replace($this->MBEncode("\n$"),$this->MBEncode("\r\n"), $file);
    }

    protected function MBEncode($text){
        return mb_convert_encoding($text, "UTF-16LE", "UTF-8");
    }

    protected function MBDecode($text){
        return mb_convert_encoding($text, "UTF-8", "UTF-16LE");
    }
}

