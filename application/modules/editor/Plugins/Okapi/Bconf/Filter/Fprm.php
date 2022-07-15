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

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class representing a fprm file
 * There are generally three types of FPRM settings:
 * - properties: Java properties text/x-properties (key-value pairs seperated by "=", these always start with "#v1", e.g. okf_html
 * - xml: xml-based, which always start with "<?xml", e.g. okf_xml
 * - yaml: indented hierarchy of properties, e.g. okf_html
 * - plain: a special format seems "okf_wiki", which seems to be JSON-like (without quotes), we include this in "indented hierarchy"
 */
final class editor_Plugins_Okapi_Bconf_Filter_Fprm extends editor_Plugins_Okapi_Bconf_ResourceFile {

    /**
     * @var string
     */
    const TYPE_XPROPERTIES = 'properties';
    /**
     * @var string
     */
    const TYPE_XML = 'xml';
    /**
     * @var string
     */
    const TYPE_YAML = 'yaml';
    /**
     * @var string
     */
    const TYPE_PLAIN = 'plain';
    /**
     * There is no other way to detect yaml than by looking into it, so we need to encode that statically
     * @var array
     */
    const YAML_TYPES = ['okf_html', 'okf_xml', 'okf_xmlstream', 'okf_doxygen'];
    /**
     * What kind of data 'okf_wiki' contains is really strange, it seems to be "JSON without quotes". We cannot validate it ...
     * @var array
     */
    const PLAIN_TYPES = ['okf_wiki'];

    /**
     * Can be: "properties" | "xml" | "plain" | "yaml"
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
     * @return string
     */
    public function getOkapiType() : string {
        $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($this->getIdentifier());
        return $idata->type;
    }

    /**
     * @return string
     */
    public function getIdentifier() : string {
        return editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($this->path);
    }

    /**
     * Validates a FPRM based on it's type
     * @return bool
     */
    public function validate(bool $forImport=false) : bool {

        // XML can be validated with the XML-Parser
        if($this->type == self::TYPE_XML){
            $parser = new editor_Utils_Dom();
            $parser->loadXML($this->content);
            // sloppy checking here as we do not know how tolerant longhorn actually is
            if($parser->isValid()){
                return true;
            }
            // DEBUG
            if($this->doDebug){ error_log('FPRM FILE '.basename($this->path).' of type '.$this->type.' is invalid: could not parse XML'); }
            $this->validationError = 'Invalid XML';
            return false;
        }
        if($this->type == self::TYPE_YAML){
            try {
                $result = Yaml::parse($this->content);
            } catch (ParseException $exception) {
                // DEBUG
                if($this->doDebug){ error_log('FPRM FILE '.basename($this->path).' of type '.$this->type.' is invalid: could not parse YAML'); }
                $this->validationError = 'Invalid YAML: '.$exception->getMessage();
                return false;
            }
            return true;
        }
        if($this->type == self::TYPE_XPROPERTIES){
            $xProperties = new editor_Plugins_Okapi_Bconf_Filter_XProperties($this->path, $this->content);
            if($xProperties->validate($forImport)){
                // if our content was missing some values, we "inherit" them by the default FPRMs
                if($xProperties->hasToBeRepaired()){
                    if($this->doDebug || ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing')){ error_log('FPRM prosessing: filter '.$this->getIdentifier().' was missing some values that have been complemented'); }
                    $this->content = $xProperties->getContent();
                }
                return true;
            }
            // DEBUG
            if($this->doDebug){ error_log('FPRM FILE '.basename($this->path).' of type '.$this->type.' is invalid'); }
            $this->validationError = 'Invalid x-properties: '."\n".$xProperties->getValidationError();
            return false;
        }
        // plain text must have characters, what else can we check ?
        if($this->getContentLength() > 0){
            return true;
        }
        // DEBUG
        if($this->doDebug){ error_log('FPRM FILE '.basename($this->path).' of type '.$this->type.' is invalid: No content found'); }
        $this->validationError = 'No content found';
        return false;
    }

    /**
     * Evaluates the type of FPRM we have
     */
    private function evaluateType(){
        if(mb_substr(ltrim($this->content), 0, 3) === "#v1"){
            $this->type = self::TYPE_XPROPERTIES;
            $this->mime = 'text/x-properties';
        } else if(mb_substr(ltrim($this->content), 0, 5) === "<?xml"){
            $this->type = self::TYPE_XML;
            $this->mime = 'text/xml';
        } else if(in_array($this->getOkapiType(), self::YAML_TYPES)){
            $this->type = self::TYPE_YAML;
            $this->mime = 'text/x-yaml';
        } else if(in_array($this->getOkapiType(), self::PLAIN_TYPES)){
            $this->type = self::TYPE_PLAIN;
            $this->mime = 'text/plain';
        } else {
            throw new ZfExtended_Exception('UNKNOWN content-type in FPRM '.$this->path);
        }
    }
}