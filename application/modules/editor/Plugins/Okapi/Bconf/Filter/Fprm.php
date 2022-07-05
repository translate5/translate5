<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Class representing a fprm file
 * There are generally three types of FPRM settings:
 * - properties: Java properties text/x-properties (key-value pairs seperated by "=", these always start with "#v1", e.g. okf_html
 * - xml: xml-based, which always start with "<?xml", e.g. okf_xml
 * - plain: indented hierarchy of properties, e.g. okf_html
 * - a special format seems "okf_wiki", which seems to be JSON-like (without quotes), we include this in "indented hierarchy"
 */
final class editor_Plugins_Okapi_Bconf_Filter_Fprm extends editor_Plugins_Okapi_Bconf_ResourceFile {

    /**
     * Can be: "properties" | "xml" | "plain"
     * @var string
     */
    private string $type;

    /**
     * @param string $path
     * @param string|null $content
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content=NULL){
        parent::__construct($path, $content);
        $this->evaluateType();
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * Validates a FPRM based on it's type
     * @return bool
     */
    public function validate() : bool {
        // plain text must have characters, what else can we check ?
        if($this->type == 'plain'){
            if($this->getContentLength() > 0){
                return true;
            }
            $this->validationError = 'No content found';
        }
        // XML can be validated with the XML-Parser
        if($this->type == 'xml'){
            $parser = new editor_Utils_Dom();
            $parser->loadXML($this->content);
            // sloppy checking here as we do not know how tolerant longhorn actually is
            if($parser->isValid()){
                return true;
            }
            $this->validationError = 'Invalid XML';
        }
        // propeties must have at least two lines
        // TODO FIXME: there should be better methods to validate a properties file
        if(count(explode("\n",$this->content)) > 1) {
            return true;
        }
        $this->validationError = 'Properties file contains no properties';
        return false;
    }

    /**
     * Evaluates the type of FPRM we have
     */
    private function evaluateType(){
        if(mb_substr($this->content, 0, 3) === "#v1"){
            $this->type = 'properties';
            $this->mime = 'text/x-properties';
        } else if(mb_substr($this->content, 0, 5) === "<?xml"){
            $this->type = 'xml';
            $this->mime = 'text/xml';
        } else {
            $this->type = 'plain';
            $this->mime = 'text/plain';
        }
    }
}