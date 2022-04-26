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
 * A simple extension of the DOMDocument class to be able to capture the errors that may occur in the process of loading HTML/XML
 * To not change the original API we do not throw exceptions but collect the errors instead of reporting them and make them accessible
 * Also an added API makes it simpler to load Unicode HTML (only XML will be loaded as UTF-8 by default) properly
 */
class editor_Utils_Dom extends DOMDocument {
    const LIBXML_LEVEL_NAME = [
        LIBXML_ERR_WARNING => 'warning',
        LIBXML_ERR_ERROR   => 'error',
        LIBXML_ERR_FATAL   => 'fatal',
    ];
    
    /**
     * Used to set UTF-8 encoding for Documents
     * @var string
     */
    const UTF8_PROCESSINGINSTRUCTION = '<?xml encoding="UTF-8">';
    /**
     * Used to set UTF-8 encoding for Markup
     * @var string
     */
    const UTF8_METATAG = '<meta http-equiv="content-type" content="text/html;charset=utf-8" />';
    
    /**
     * Helper to retrieve the inner HTML of a DOM Node
     * @param DOMNode $element
     * @return string
     */
    public static function innerHTML(DOMNode $element) : string {
        $html = '';
        foreach ($element->childNodes as $child){
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }
    /**
     * 
     * @var libXMLError[]
     */
    private $domErrors = [];
    /**
     * 
     * @var boolean
     */
    private $traceDomErrors = false;

    private DOMXPath $xpath;

    /**
     * @return DOMXPath
     */
    public function getXpath(): DOMXPath {
        return $this->xpath ?? ($this->xpath = new DOMXPath($this));
    }
    
    public function __construct($version=null, $encoding=null){
        parent::__construct($version, $encoding);
        // as long as libxml reports completely outdated errors (-> HTML 4.0.1 strict specs) we disable this
        $this->strictErrorChecking = false;
    }

    public function load ($filename, $options=NULL): bool { // returns bool when called dynamic
        $filename = realpath($filename);
        $this->domErrors = [];
        libxml_clear_errors();
        $useErrors = libxml_use_internal_errors(true);
        try {
            $result = parent::load($filename, $options);
            $this->domErrors = libxml_get_errors();
        } catch (Exception $e) {
            $this->domErrors[] = $this->createLibXmlError($e->getMessage(), $filename);
            $result = false;
        }
        libxml_use_internal_errors($useErrors);
        $this->traceWarningsAndErrors();
        return $result;
    }
    
    public function loadXML ($source, $options=NULL){
        $this->domErrors = [];
        libxml_clear_errors();
        $useErrors = libxml_use_internal_errors(true);
        try {
            $result = parent::loadXML($source, $options);
            $this->domErrors = libxml_get_errors();
        } catch (Exception $e) {
            $this->domErrors[] = $this->createLibXmlError($e->getMessage());
            $result = false;
        }
        libxml_use_internal_errors($useErrors);
        $this->traceWarningsAndErrors();
        return $result;
    }

    public function loadHTML ($source, $options=NULL){
        $this->domErrors = [];
        libxml_clear_errors();
        $useErrors = libxml_use_internal_errors(true);
        try {
            $result = parent::loadHTML($source, $options);
            $this->domErrors = libxml_get_errors();
        } catch (Exception $e) {
            $this->domErrors[] = $this->createLibXmlError($e->getMessage());
            $result = false;
        }
        libxml_use_internal_errors($useErrors);
        $this->traceWarningsAndErrors();
        return $result;
    }
    /**
     * Loads an HTML-String and forces a proper UTF-8 Encoding, returns the DOMNode-children representing the passed Markup
     * @param string $source
     * @return DOMNodeList|NULL
     */
    public function loadUnicodeMarkup (string $source){
        $result = $this->loadHTML('<html><head>'.self::UTF8_METATAG.'</head><body>'.$source.'</body>');
        if($result){
            $this->encoding = 'UTF-8';
            $body = $this->getElementsByTagName('body');
            if($body->length > 0 && $body->item(0)->hasChildNodes()){
                return $body->item(0)->childNodes;
            }
            return new DOMNodeList();
        }        
        return NULL;
    }
    /**
     * Loads an HTML-String and forces a proper UTF-8 Encoding, returns the DOMNode-children representing the passed Element Markup
     * Note that if multiple nodes/elements are passed, this will return the first Element
     * @param string $source
     * @return DOMElement|NULL
     */
    public function loadUnicodeElement (string $source){
        $nodes = $this->loadUnicodeMarkup($source);
        if($nodes != NULL){
            for($i=0; $i < $nodes->length; $i++){
                $node = $nodes->item($i);
                if($node->nodeType == XML_ELEMENT_NODE){
                    return $node;
                }
            }
        }
        return NULL;
    }
    
    public function loadHTMLFile ($filename, $options=NULL){
        $filename = realpath($filename);
        $this->domErrors = [];
        libxml_clear_errors();
        $useErrors = libxml_use_internal_errors(true);
        try {
            $result = parent::loadHTMLFile($filename, $options);
            $this->domErrors = libxml_get_errors();
        } catch (Exception $e) {
            $this->domErrors[] = $this->createLibXmlError($e->getMessage(), $filename);
            $result = false;
        }
        libxml_use_internal_errors($useErrors);
        $this->traceWarningsAndErrors();
        return $result;
    }
    /**
     * Evaluates, if a loaded Document had no fatal errors and therefore can be seen as "valid"
     * If the evaluation has to be more strict you have to use the options
     */
    /**
     * @param bool $allowErrors
     * @param bool $allowWarnings
     * @return bool
     */
    public function isValid(bool $allowErrors=true){
        if(count($this->domErrors) == 0){
            return true;
        }
        foreach($this->domErrors as $error){ /* @var $error libXMLError */
            if($error->level == LIBXML_ERR_FATAL || (!$allowErrors && $error->level == LIBXML_ERR_ERROR)){
                return false;
            }
        }
        return true;
    }
    /**
     * Evaluates, if a string is a valid XML string in the sense, taht it produces no fatal errors when loaded as such
     * @param string $string
     * @return boolean
     */
    public function isValidXmlString($string){
        //surround with dummy tags so the string validation can be done with domdocument
        $testString = '<dummytag>'.$string.'</dummytag>';
        $this->loadXML($testString);
        return $this->isValid();
    }
    /**
     * Activates error tracing using error_log
     */
    public function activateErrorTracing(){
        $this->traceDomErrors = true;
    }
    /**
     *
     * @return boolean
     */
    public function hasWarningsOrErrors(){
        return (count($this->domErrors) > 0);
    }
    /**
     * Checks, if there were any fatals, errors or warnings when loading a document
     * @return libXMLError[]
     */
    public function getWarningsAndErrors(){
        return $this->domErrors;
    }
    /**
     * Retrieves the fatals, errors and warmings as a concatenated string
     * @param string $glue
     * @param bool $verbose enables column and line number
     * @return string
     */
    public function getErrorMsg(string $glue=', ', bool $verbose = false): string{
        $errors = [];
        if(count($this->domErrors) > 0){
            foreach($this->domErrors as $error){ /* @var $error libXMLError */
                if($verbose){
                    $errorName = $this::LIBXML_LEVEL_NAME[$error->level];
                    $errors[] .= "$errorName@$error->line,$error->column: $error->message";
                } else {
                    $errors[] = $this->createLibXmlErrorMsg($error);
                }
            }
        }
        return implode($glue, $errors);
    }
    /**
     * Traces the capured errors if there were any
     */
    private function traceWarningsAndErrors(){
        if($this->traceDomErrors && count($this->domErrors) > 0){
            foreach($this->domErrors as $error){ /* @var $error libXMLError */
                error_log($this->createLibXmlErrorMsg($error));
            }
        }
    }
    /**
     * 
     * @param libXMLError $error
     * @return string
     */
    private function createLibXmlErrorMsg(libXMLError $error): string{
        $errname = ($error->level == LIBXML_ERR_FATAL) ? 'FATAL ERROR' : (($error->level == LIBXML_ERR_ERROR) ? 'ERROR' : 'WARNING');
        return 'LibXML '.$errname.': '.$error->message;
    }
    /**
     * 
     * @param string $message
     * @param string $file
     * @param int $level
     * @return libXMLError
     */
    private function createLibXmlError(string $message, string $file=null, int $level=LIBXML_ERR_FATAL): libXMLError {
        $error = new libXMLError();
        $error->message = $message;
        $error->file = $file;
        $error->level = $level;
        return $error;
    }

    public function query(string $selector) {
        return $this->getXpath()->query($selector);
    }
}
